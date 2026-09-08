# Organisaatio
$org = "Savo-Consortium-of-Education"

# Kysy repo-nimet
$sourceRepoName = Read-Host "Syötä lähde-repon nimi (esim. pienyrittajan-taloushallinto)"
$targetRepoName = Read-Host "Syötä kohde-repon nimi (esim. vx)"

# Muodosta täydelliset repo-polut
$sourceRepo = "$org/$sourceRepoName"
$targetRepo = "$org/$targetRepoName"

Write-Host ""
Write-Host "Lähde: $sourceRepo" -ForegroundColor Yellow
Write-Host "Kohde: $targetRepo" -ForegroundColor Yellow
Write-Host ""

# Hae kaikki issueita lähde-reposta
Write-Host "Haetaan issueita..." -ForegroundColor Yellow
$issues = gh issue list -R $sourceRepo --state open --json number,title,body | ConvertFrom-Json

# Tarkista, löytyikö issueita
if ($issues.Count -eq 0) {
    Write-Host "Ei issueita löytynyt!" -ForegroundColor Red
    exit
}

Write-Host "Löytyi $($issues.Count) issuea. Aloitetaan kopiointi..." -ForegroundColor Green
Write-Host ""

# Kopioi jokainen issue
$i = 0
foreach ($issue in $issues) {
    $i++
    $title = $issue.title
    $body = $issue.body
    
    Write-Host "[$i/$($issues.Count)] Kopioidaan: $title" -ForegroundColor Cyan
    
    gh issue create -R $targetRepo --title "$title" --body "$body" | Out-Null
}

Write-Host ""
Write-Host "✅ Kaikki $($issues.Count) issuea kopioitu!" -ForegroundColor Green
