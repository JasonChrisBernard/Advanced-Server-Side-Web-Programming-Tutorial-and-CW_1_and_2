<div class="card shadow-sm mb-3">
    <div class="card-body">
        <h3>AR Alumni Public Developer API</h3>

        <p class="text-muted mb-2">
            This Swagger documentation explains how external AR clients can access the Alumni Influencer API using bearer tokens.
        </p>

        <p class="mb-0">
            To test protected endpoints, click <strong>Authorize</strong> inside Swagger UI and paste your API key.
        </p>
    </div>
</div>

<div id="swagger-ui"></div>

<link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui.css">

<script src="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui-bundle.js"></script>
<script src="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui-standalone-preset.js"></script>

<script>
    window.onload = function () {
        window.ui = SwaggerUIBundle({
            url: "<?php echo site_url('api-docs/openapi'); ?>",
            dom_id: "#swagger-ui",
            deepLinking: true,
            persistAuthorization: true,
            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIStandalonePreset
            ],
            layout: "BaseLayout"
        });
    };
</script>