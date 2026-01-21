param(
    [Parameter(Mandatory=$true, HelpMessage="Palavras-chave para busca (ex: chrome, note)")]
    [string[]]$Keywords,

    [Parameter(HelpMessage="Formato de saída: text, json, csv")]
    [ValidateSet("text", "json", "csv")]
    [string]$Format = "text",

    [Parameter(HelpMessage="Exibir detalhes adicionais")]
    [switch]$Detailed
)

# 1. Identificar processos ativos e 2. Filtrar por palavras-chave
$processes = Get-Process | Where-Object {
    $proc = $_
    $match = $false
    foreach ($kw in $Keywords) {
        # Busca case-insensitive por padrão no PowerShell
        if ($proc.ProcessName -like "*$kw*" -or $proc.MainWindowTitle -like "*$kw*") {
            $match = $true
            break
        }
    }
    $match
}

if (-not $processes) {
    Write-Warning "Nenhum processo encontrado com as palavras-chave: $($Keywords -join ', ')"
    exit
}

# 3. Exibir informações relevantes
$results = $processes | ForEach-Object {
    $uptime = "N/A"
    $startTimeStr = "N/A"
    
    try {
        # Alguns processos de sistema não permitem acesso ao StartTime
        if ($_.StartTime) {
            $timeSpan = (Get-Date) - $_.StartTime
            $uptime = "{0:hh\:mm\:ss}" -f $timeSpan
            # Se for mais de 1 dia, adicionar dias
            if ($timeSpan.Days -gt 0) {
                $uptime = "$($timeSpan.Days)d " + $uptime
            }
            $startTimeStr = $_.StartTime.ToString("yyyy-MM-dd HH:mm:ss")
        }
    } catch {
        # Ignora erro de acesso negado
    }

    $memMB = [math]::Round($_.WorkingSet / 1MB, 2)

    [PSCustomObject]@{
        Id          = $_.Id
        Name        = $_.ProcessName
        MemoryMB    = $memMB
        Duration    = $uptime
        StartTime   = $startTimeStr
        Title       = $_.MainWindowTitle
    }
}

# Controle de nível de detalhe
if (-not $Detailed) {
    $results = $results | Select-Object Id, Name, MemoryMB, Duration
}

# 5. Opções de saída formatada
switch ($Format) {
    "json" { 
        $results | ConvertTo-Json -Depth 2 
    }
    "csv" { 
        $results | ConvertTo-Csv -NoTypeInformation 
    }
    "text" { 
        # Formatação de tabela bonita para texto
        $results | Format-Table -AutoSize | Out-String 
    }
}
