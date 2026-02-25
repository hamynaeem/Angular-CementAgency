Param(
    [string]$SqlFile = "fix_sp_ManageCashbook_full_fix.sql",
    [string]$DbName = "db_cement",
    [string]$DbUser = "root",
    [string]$DbPass = ""
)

$here = Split-Path -Parent $MyInvocation.MyCommand.Definition
Push-Location $here

Write-Host "Running SQL fix: $SqlFile"

$php = "php"

# Run the PHP SQL runner. If password is empty it will prompt.
$args = @('run_sql.php', $SqlFile, $DbName, $DbUser, $DbPass)
$proc = Start-Process -FilePath $php -ArgumentList $args -NoNewWindow -Wait -PassThru
if ($proc.ExitCode -ne 0) {
    Write-Error "run_sql.php failed with exit code $($proc.ExitCode)"
    Pop-Location
    exit $proc.ExitCode
}

Start-Sleep -Seconds 1

# Test POST a voucher to the local API
$uri = "http://localhost:4200/apis/index.php/tasks/vouchers?bid=1"
$voucher = @{
    Date = (Get-Date).ToString('yyyy-MM-dd')
    RouteID = '0'
    AcctTypeID = '0'
    CustomerID = '268'
    RefID = '0'
    Description = 'Test voucher from run_fix_and_test.ps1'
    Debit = '0'
    Credit = '100'
    IsPosted = 0
    FinYearID = 1
    RefType = 0
    BusinessID = 1
}
$json = $voucher | ConvertTo-Json -Depth 5
Write-Host "Posting test voucher to $uri"
Write-Host $json
try {
    $resp = Invoke-RestMethod -Uri $uri -Method Post -Body $json -ContentType 'application/json'
    Write-Host "Response:"; $resp | ConvertTo-Json
} catch {
    Write-Error "POST failed: $($_.Exception.Message)"
    if ($_.Exception.Response) {
        try {
            $stream = $_.Exception.Response.GetResponseStream()
            $reader = New-Object System.IO.StreamReader($stream)
            $body = $reader.ReadToEnd()
            Write-Host "Response body:"; Write-Host $body
        } catch {
            Write-Error "Failed reading response body"
        }
    }
}

# Tail latest CodeIgniter log (if present)
$logdir = Join-Path $here 'application\logs'
if (Test-Path $logdir) {
    $latest = Get-ChildItem $logdir -File | Sort-Object LastWriteTime -Descending | Select-Object -First 1
    if ($latest) {
        Write-Host "Tailing last 200 lines of $($latest.Name)"
        Get-Content $latest.FullName -Tail 200
    } else {
        Write-Host "No log files found in $logdir"
    }
} else {
    Write-Host "Log directory not found: $logdir"
}

Pop-Location
