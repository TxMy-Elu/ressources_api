# ─────────────────────────────────────────────────────────────────────────────
# Script de configuration GitHub — Labels + Project Board
# Usage : .\scripts\setup-github.ps1 -Token "ghp_xxxx"
# ─────────────────────────────────────────────────────────────────────────────

param(
    [Parameter(Mandatory=$true)]
    [string]$Token
)

$repo  = "TxMy-Elu/ressources_api"
$headers = @{
    Authorization = "Bearer $Token"
    Accept        = "application/vnd.github+json"
    "X-GitHub-Api-Version" = "2022-11-28"
}

# ── Labels ────────────────────────────────────────────────────────────────────

$labels = @(
    @{ name = "critique";    color = "B60205"; description = "Application inaccessible / données compromises" }
    @{ name = "haute";       color = "D93F0B"; description = "Fonctionnalité majeure bloquée" }
    @{ name = "moyenne";     color = "E4E669"; description = "Fonctionnalité dégradée" }
    @{ name = "faible";      color = "0E8A16"; description = "Problème mineur / cosmétique" }
    @{ name = "incident";    color = "5319E7"; description = "Incident en production" }
    @{ name = "hotfix";      color = "C5DEF5"; description = "Correctif urgent" }
    @{ name = "en cours";    color = "0075CA"; description = "Traitement en cours" }
    @{ name = "en test";     color = "BFD4F2"; description = "Correctif en cours de validation" }
)

Write-Host "`n=== Création des labels ===" -ForegroundColor Cyan

foreach ($label in $labels) {
    $body = $label | ConvertTo-Json
    $response = Invoke-RestMethod `
        -Uri "https://api.github.com/repos/$repo/labels" `
        -Method Post `
        -Headers $headers `
        -Body $body `
        -ContentType "application/json" `
        -ErrorAction SilentlyContinue

    if ($response.name) {
        Write-Host "  ✓ Label '$($label.name)' créé" -ForegroundColor Green
    } else {
        Write-Host "  ~ Label '$($label.name)' existe déjà ou erreur" -ForegroundColor Yellow
    }
}

# ── GitHub Project Board ──────────────────────────────────────────────────────

Write-Host "`n=== Création du Project Board ===" -ForegroundColor Cyan

# Récupérer l'owner ID (nécessaire pour l'API Projects v2)
$userQuery = '{"query": "{ viewer { id login } }"}'
$user = Invoke-RestMethod `
    -Uri "https://api.github.com/graphql" `
    -Method Post `
    -Headers $headers `
    -Body $userQuery `
    -ContentType "application/json"

$ownerId = $user.data.viewer.id
Write-Host "  Owner: $($user.data.viewer.login) ($ownerId)"

# Créer le project
$projectMutation = @"
{
  "query": "mutation { createProjectV2(input: { ownerId: \"$ownerId\", title: \"(Re)Sources — Suivi des incidents\" }) { projectV2 { id url } } }"
}
"@

$project = Invoke-RestMethod `
    -Uri "https://api.github.com/graphql" `
    -Method Post `
    -Headers $headers `
    -Body $projectMutation `
    -ContentType "application/json"

if ($project.data.createProjectV2.projectV2.url) {
    $projectUrl = $project.data.createProjectV2.projectV2.url
    Write-Host "  ✓ Project créé : $projectUrl" -ForegroundColor Green
    Write-Host "`n  → Ouvre le project et ajoute manuellement les colonnes :" -ForegroundColor Yellow
    Write-Host "     À faire | En cours | En test | Terminé" -ForegroundColor Yellow
} else {
    Write-Host "  Erreur création project : $($project | ConvertTo-Json -Depth 3)" -ForegroundColor Red
}

Write-Host "`n=== Terminé ===" -ForegroundColor Cyan
