# Simple helper: POST a voucher and tail latest CI log
$uri = 'http://localhost:4200/apis/index.php/tasks/vouchers?bid=1'
$voucher = @{
    Date = '2025-09-22'
    RouteID = ''
    AcctTypeID = '1'
    CustomerID = '268'
    RefID = '0'
    Description = 'Save from run_post_voucher.ps1'
    Debit = '0'
    Credit = '100'
    IsPosted = 0
    FinYearID = 1
    RefType = 0
    BusinessID = 1
}
$json = $voucher | ConvertTo-Json -Depth 5
Write-Host 'POST body:'
Write-Host $json
try {
    $resp = Invoke-RestMethod -Uri $uri -Method Post -Body $json -ContentType 'application/json' -ErrorAction Stop
    Write-Host 'POST response:'
    $resp | ConvertTo-Json
} catch {
    Write-Host 'POST failed:'
    if ($_.Exception.Response) {
        $r = $_.Exception.Response
        $sr = New-Object System.IO.StreamReader($r.GetResponseStream())
        $body = $sr.ReadToEnd()
        Write-Host $body
    } else {
        Write-Host $_.Exception.Message
    }
}

# Tail the latest log
$logdir = Join-Path $PSScriptRoot 'application\logs'
if (Test-Path $logdir) {
    $latest = Get-ChildItem $logdir -File | Sort-Object LastWriteTime -Descending | Select-Object -First 1
    if ($latest) {
        Write-Host "--- Tailing $($latest.Name) ---"
        Get-Content $latest.FullName -Tail 200
    } else {
        Write-Host 'No log files found'
    }
} else {
    Write-Host "Log directory not found: $logdir"
}
