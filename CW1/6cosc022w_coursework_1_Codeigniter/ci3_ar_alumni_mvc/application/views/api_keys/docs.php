<div class="card shadow-sm">
    <div class="card-body">
        <h3>API Access Guide</h3>

        <p class="text-muted">
            Different clients should use separate API keys. For example, the AR headset client, mobile app, and test client should each have their own key. This allows the developer to monitor usage separately and revoke only one client if needed.
        </p>

        <h5>Authentication Method</h5>

        <p>
            Every protected API request must include this HTTP header:
        </p>

        <pre class="bg-light border p-3">Authorization: Bearer YOUR_API_KEY</pre>

        <h5>Available Client Scope</h5>

        <table class="table table-bordered">
            <tr>
                <th>Scope</th>
                <th>Meaning</th>
            </tr>

            <tr>
                <td><code>read:alumni</code></td>
                <td>Allows the client to read featured alumni and public alumni profile data.</td>
            </tr>
        </table>

        <h5>Protected API Endpoints</h5>

        <table class="table table-bordered">
            <tr>
                <th>Method</th>
                <th>Endpoint</th>
                <th>Description</th>
            </tr>

            <tr>
                <td>GET</td>
                <td><code><?php echo site_url('api/v1/featured-today'); ?></code></td>
                <td>Returns the featured alumnus for today.</td>
            </tr>

            <tr>
                <td>GET</td>
                <td><code><?php echo site_url('api/v1/featured/YYYY-MM-DD'); ?></code></td>
                <td>Returns the featured alumnus for a selected date.</td>
            </tr>

            <tr>
                <td>GET</td>
                <td><code><?php echo site_url('api/v1/alumni/USER_ID'); ?></code></td>
                <td>Returns a public alumni profile by user ID.</td>
            </tr>
        </table>

        <h5>Example PowerShell Test</h5>

        <pre class="bg-light border p-3">$token = "PASTE_YOUR_API_KEY_HERE"

Invoke-RestMethod `
  -Uri "http://localhost:8080/index.php/api/v1/featured-today" `
  -Headers @{ Authorization = "Bearer $token" } `
  -Method GET</pre>

        <h5>Example Postman Setup</h5>

        <p>
            In Postman, open the Headers tab and add:
        </p>

        <table class="table table-bordered">
            <tr>
                <th>Key</th>
                <th>Value</th>
            </tr>

            <tr>
                <td><code>Authorization</code></td>
                <td><code>Bearer YOUR_API_KEY</code></td>
            </tr>
        </table>

        <a class="btn btn-outline-secondary" href="<?php echo site_url('api-keys'); ?>">
            Back to API Keys
        </a>

        <a class="btn btn-primary mb-3" href="<?php echo site_url('api-docs'); ?>">
            Open Swagger Documentation
        </a>
    </div>
</div>