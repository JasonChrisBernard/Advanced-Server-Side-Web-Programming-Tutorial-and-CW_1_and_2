# Scoped API Guide

All protected endpoints use bearer-token authentication:

```http
Authorization: Bearer YOUR_API_KEY
```

## Client Scopes

| Client platform | Scopes | Purpose |
| --- | --- | --- |
| `mobile_ar_app` | `read:alumni_of_day` | Mobile/AR client can read the featured alumnus only. |
| `analytics_dashboard` | `read:analytics`, `read:alumni` | Dashboard can read chart datasets, summary totals, and alumni profile drill-down data. |

## Endpoints

| Method | Endpoint | Required scope | Purpose |
| --- | --- | --- | --- |
| `GET` | `/index.php/api/v1/health` | Public | API health check. |
| `GET` | `/index.php/api/v1/mobile/alumni-of-day` | `read:alumni_of_day` | Today's AR Alumni of the Day. |
| `GET` | `/index.php/api/v1/featured/{date}` | `read:alumni_of_day` | Featured alumnus for a specific date. |
| `GET` | `/index.php/api/v1/alumni/{id}` | `read:alumni` | Public alumni profile and profile sections. |
| `GET` | `/index.php/api/v1/analytics/summary` | `read:analytics` | Dashboard summary totals. |
| `GET` | `/index.php/api/v1/analytics/alumni` | `read:alumni` | Alumni table data for dashboard analysis. |
| `GET` | `/index.php/api/v1/analytics/certifications` | `read:analytics` | Certification aggregate data. |
| `GET` | `/index.php/api/v1/analytics/industries` | `read:analytics` | Industry aggregate data. |
| `GET` | `/index.php/api/v1/analytics/charts` | `read:analytics` | All chart datasets and insight cards used by the graph report. |

## PowerShell Examples

Analytics chart datasets:

```powershell
$token = "PASTE_ANALYTICS_DASHBOARD_KEY_HERE"

Invoke-RestMethod `
  -Uri "http://localhost:8081/index.php/api/v1/analytics/charts" `
  -Headers @{ Authorization = "Bearer $token" } `
  -Method GET
```

Mobile Alumni of the Day:

```powershell
$token = "PASTE_MOBILE_AR_APP_KEY_HERE"

Invoke-RestMethod `
  -Uri "http://localhost:8081/index.php/api/v1/mobile/alumni-of-day" `
  -Headers @{ Authorization = "Bearer $token" } `
  -Method GET
```

## Error Responses

- `401` means the bearer token is missing, invalid, or revoked.
- `403` means the key exists but does not include the endpoint's required scope.
- `429` means the key exceeded the configured per-minute rate limit.
