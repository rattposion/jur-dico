<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Record;
use App\Models\Audit;
use App\Core\JSONStream;
use Exception;

class ImportService
{
    private array $config;
    private array $logs = [];
    private int $successCount = 0;
    private int $errorCount = 0;
    private array $typeCounts = [];
    private ?string $forcedType = null;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function processFile(array $file, ?string $forcedType = null): array
    {
        $this->resetStats();
        $this->forcedType = $forcedType;
        
        $fileName = $file['name'];
        $tmpPath = $file['tmp_name'];
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $this->log("Iniciando processamento do arquivo: $fileName");

        if (!in_array($fileType, ['json', 'csv', 'xlsx'])) {
            $this->log("Erro: Formato de arquivo não suportado ($fileType). Use JSON, CSV ou XLSX.", 'error');
            return $this->getResult();
        }

        try {
            switch ($fileType) {
                case 'json':
                    $this->processJson($tmpPath);
                    break;
                case 'csv':
                    $this->processCsv($tmpPath);
                    break;
                case 'xlsx':
                    $this->processXlsx($tmpPath);
                    break;
            }
        } catch (Exception $e) {
            $this->log("Erro fatal durante importação: " . $e->getMessage(), 'error');
        }

        // Audit Log
        $userId = \App\Core\Auth::user()['id'] ?? null;
        Audit::log((int)$userId ?: null, 'import_file', null, [
            'file' => $fileName,
            'success' => $this->successCount,
            'errors' => $this->errorCount,
            'details' => array_slice($this->logs, 0, 10) // Log first 10 details to avoid db bloat
        ]);

        return $this->getResult();
    }

    private function processJson(string $path): void
    {
        foreach (JSONStream::iterateObjects($path) as $record) {
            $this->importRecord($record);
        }
    }

    private function processCsv(string $path): void
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            throw new Exception("Não foi possível abrir o arquivo CSV.");
        }

        $header = fgetcsv($handle, 0, ','); // Assume comma separated
        if (!$header) {
            throw new Exception("Arquivo CSV vazio ou inválido.");
        }
        
        // Normalize header keys (remove BOM, trim, lowercase)
        $header = array_map(function($h) {
            return trim(strtolower(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $h)));
        }, $header);

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            if (count($row) !== count($header)) {
                $this->log("Linha CSV ignorada: contagem de colunas incompatível.", 'warning');
                continue;
            }
            $data = array_combine($header, $row);
            $this->importRecord($data);
        }
        fclose($handle);
    }

    private function processXlsx(string $path): void
    {
        // Check for ZipArchive support (XLSX is a zip)
        if (!class_exists('ZipArchive')) {
            throw new Exception("A extensão ZipArchive do PHP é necessária para processar arquivos XLSX.");
        }

        // Basic XML extraction strategy for XLSX (since we don't have PhpSpreadsheet)
        // This is a simplified reader that looks for shared strings and sheet data.
        // For production environments without libraries, it's safer to ask for CSV.
        // However, I'll attempt a basic extraction of the first sheet.
        
        $zip = new \ZipArchive;
        if ($zip->open($path) === true) {
            // 1. Read Shared Strings
            $sharedStrings = [];
            if ($zip->locateName('xl/sharedStrings.xml') !== false) {
                $xml = simplexml_load_string($zip->getFromName('xl/sharedStrings.xml'));
                $namespaces = $xml->getNamespaces(true);
                $xml->registerXPathNamespace('x', $namespaces[''] ?? 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                foreach ($xml->xpath('//x:si') as $si) {
                    $sharedStrings[] = (string)$si->t;
                }
            }

            // 2. Read Sheet 1
            if ($zip->locateName('xl/worksheets/sheet1.xml') !== false) {
                $xml = simplexml_load_string($zip->getFromName('xl/worksheets/sheet1.xml'));
                $namespaces = $xml->getNamespaces(true);
                $xml->registerXPathNamespace('x', $namespaces[''] ?? 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                
                $rows = $xml->xpath('//x:row');
                $header = [];
                $firstRow = true;

                foreach ($rows as $row) {
                    $rowData = [];
                    $cells = $row->xpath('x:c');
                    
                    // Need to handle empty cells and correct column indexing ideally, 
                    // but simple iteration works for simple tables.
                    foreach ($cells as $cell) {
                        $val = (string)$cell->v;
                        $type = (string)$cell['t'];
                        
                        if ($type === 's' && isset($sharedStrings[$val])) {
                            $val = $sharedStrings[$val];
                        }
                        $rowData[] = $val;
                    }

                    if ($firstRow) {
                        $header = array_map(function($h) {
                            return trim(strtolower(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $h)));
                        }, $rowData);
                        $firstRow = false;
                    } else {
                        // Pad row data if missing trailing cells
                        if (count($rowData) < count($header)) {
                             $rowData = array_pad($rowData, count($header), null);
                        }
                        // If row has more columns, slice it
                        if (count($rowData) > count($header)) {
                             $rowData = array_slice($rowData, 0, count($header));
                        }
                        
                        $data = array_combine($header, $rowData);
                        $this->importRecord($data);
                    }
                }
            } else {
                throw new Exception("Planilha inválida: sheet1.xml não encontrado.");
            }
            $zip->close();
        } else {
            throw new Exception("Não foi possível abrir o arquivo XLSX.");
        }
    }

    private function detectRecordType(array $data): string
    {
        // Helper to check fields
        $check = function($keys, $terms) use ($data) {
            foreach ($keys as $key) {
                if (isset($data[$key]) && is_string($data[$key])) {
                    foreach ((array)$terms as $term) {
                        if (stripos($data[$key], $term) !== false) return true;
                    }
                }
            }
            return false;
        };

        // ACP: Ação Civil Pública
        if ($check(['classe', 'assunto', 'descricaoClasse'], ['Ação Civil Pública', 'Civil Pública'])) {
            return 'acp';
        }

        // AP: Ação Popular
        if ($check(['classe', 'assunto', 'descricaoClasse'], ['Ação Popular'])) {
            return 'ap';
        }

        // MS: Mandado de Segurança Coletivo
        if ($check(['classe', 'assunto', 'descricaoClasse'], ['Mandado de Segurança Coletivo', 'Segurança Coletivo'])) {
            return 'msc';
        }

        // ACOR: Processo Estrutural ACOR
        if ($check(['tipo', 'classe', 'assunto'], ['ACOR'])) {
            return 'acor';
        }

        // DTXT: Processo Estrutural DTXT
        if ($check(['tipo', 'classe', 'assunto'], ['DTXT'])) {
            return 'dtxt';
        }

        return 'general';
    }

    private function importRecord(array $data): void
    {
        try {
            // Validate required fields (at least some identifier)
            if (empty($data['numeroProcesso']) && empty($data['numero']) && empty($data['id']) && empty($data['numeroRegistro'])) {
                // Skip empty rows silently or log as warning
                return;
            }

            // Detect type if not explicitly set
            if ($this->forcedType) {
                $data['record_type'] = $this->forcedType;
            } elseif (!isset($data['record_type'])) {
                $data['record_type'] = $this->detectRecordType($data);
            }
            
            $type = $data['record_type'];
            $this->typeCounts[$type] = ($this->typeCounts[$type] ?? 0) + 1;

            Record::upsert($data);
            $this->successCount++;
        } catch (Exception $e) {
            $this->errorCount++;
            $this->log("Erro ao importar registro: " . $e->getMessage(), 'error');
        }
    }

    private function log(string $message, string $type = 'info'): void
    {
        $this->logs[] = [
            'timestamp' => date('H:i:s'),
            'type' => $type,
            'message' => $message
        ];
    }

    private function resetStats(): void
    {
        $this->logs = [];
        $this->successCount = 0;
        $this->errorCount = 0;
        $this->typeCounts = [];
    }

    private function getResult(): array
    {
        arsort($this->typeCounts);
        $dominant = array_key_first($this->typeCounts);
        
        return [
            'success' => $this->successCount > 0,
            'imported_count' => $this->successCount,
            'error_count' => $this->errorCount,
            'logs' => $this->logs,
            'dominant_type' => $dominant
        ];
    }
}
