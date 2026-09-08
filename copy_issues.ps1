
# Kysy lähde- ja kohde-repot
$sourceRepo = Read-Host "Syötä lähde-repo (esim. Savo-Consortium-of-Education/pienyrittajan-taloushallinto)"
$targetRepo = Read-Host "Syötä kohde-repo (esim. Savo-Consortium-of-Education/vx)"

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
