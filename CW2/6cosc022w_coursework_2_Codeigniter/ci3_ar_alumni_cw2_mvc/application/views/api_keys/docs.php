<div class="card shadow-sm">
    <div class="card-body">
        <h3>Scoped API Access Guide</h3>

        <p class="text-muted">
            Create separate API keys for each client platform. The Mobile AR App and University Analytics Dashboard now receive different scopes, so a leaked mobile key cannot read dashboard analytics.
        </p>

        <h5>Authentication Method</h5>

        <pre class="bg-light border p-3">Authorization: Bearer YOUR_API_KEY</pre>

        <h5>Client Platforms and Scopes</h5>

        <table class="table table-bordered align-middle">
            <tr>
                <th>Client Platform</th>
                <th>Scopes</th>
                <th>Allowed Use</th>
            </tr>

            <tr>
                <td><code>mobile_ar_app</code></td>
                <td><code>read:alumni_of_day</code></td>
                <td>Read the featured Alumni of the Day for the AR/mobile client.</td>
            </tr>

            <tr>
                <td><code>analytics_dashboard</code></td>
                <td><code>read:analytics</code>, <code>read:alumni</code></td>
                <td>Read dashboard chart datasets, summary totals, and alumni drill-down records.</td>
            </tr>
        </table>

        <h5>Protected API Endpoints</h5>

        <table class="table table-bordered align-middle">
            <tr>
                <th>Method</th>
                <th>Endpoint</th>
                <th>Required Scope</th>
                <th>Description</th>
            </tr>

            <tr>
                <td>GET</td>
                <td><code><?php echo site_url('api/v1/mobile/alumni-of-day'); ?></code></td>
                <td><code>read:alumni_of_day</code></td>
                <td>Returns today&apos;s AR Alumni of the Day.</td>
            </tr>

            <tr>
                <td>GET</td>
                <td><code><?php echo site_url('api/v1/featured/YYYY-MM-DD'); ?></code></td>
                <td><code>read:alumni_of_day</code></td>
                <td>Returns the featured alumnus for a selected date.</td>
            </tr>

            <tr>
                <td>GET</td>
                <td><code><?php echo site_url('api/v1/alumni/USER_ID'); ?></code></td>
                <td><code>read:alumni</code></td>
                <td>Returns a public alumni profile with profile sections.</td>
            </tr>

            <tr>
                <td>GET</td>
                <td><code><?php echo site_url('api/v1/analytics/summary'); ?></code></td>
                <td><code>read:analytics</code></td>
                <td>Returns dashboard summary totals.</td>
            </tr>

            <tr>
                <td>GET</td>
                <td><code><?php echo site_url('api/v1/analytics/charts'); ?></code></td>
                <td><code>read:analytics</code></td>
                <td>Returns all chart datasets and colour-coded insight metadata.</td>
            </tr>
        </table>

        <h5>Example PowerShell Test</h5>

        <pre class="bg-light border p-3">$token = "PASTE_ANALYTICS_DASHBOARD_KEY_HERE"

Invoke-RestMethod `
  -Uri "<?php echo site_url('api/v1/analytics/charts'); ?>" `
  -Headers @{ Authorization = "Bearer $token" } `
  -Method GET</pre>

        <h5>Security Controls</h5>

        <ul>
            <li>API keys are stored as SHA-256 hashes and only the prefix is displayed after creation.</li>
            <li>Each API request is logged with endpoint, scope, status code, IP address, and user agent.</li>
            <li>Requests are rate limited per API key and CORS is restricted to configured local origins.</li>
        </ul>

        <a class="btn btn-outline-secondary" href="<?php echo site_url('api-keys'); ?>">
            Back to API Keys
        </a>

        <a class="btn btn-primary" href="<?php echo site_url('api-docs'); ?>">
            Open Swagger Documentation
        </a>
    </div>
</div>
