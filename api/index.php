<?php
/**
 * Server Room Monitor - Main Entry Point
 * Serves the dashboard HTML
 */

// Get the path to the parent directory (where server-room-monitor.html is)
$htmlFile = dirname(__DIR__) . '/server-room-monitor.html';

if (file_exists($htmlFile)) {
    // Read and output the HTML file
    readfile($htmlFile);
} else {
    http_response_code(404);
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Error</title>
        <style>
            body { font-family: monospace; padding: 40px; background: #f0ede8; }
            .error { background: #fdf0f0; border: 2px solid #d94040; padding: 20px; }
            h1 { color: #d94040; }
            code { background: #f5f2ee; padding: 2px 6px; }
        </style>
    </head>
    <body>
        <div class="error">
            <h1>Dashboard Not Found</h1>
            <p>Please ensure <code>server-room-monitor.html</code> is in the project root.</p>
            <p>Expected path: ' . htmlspecialchars($htmlFile) . '</p>
        </div>
    </body>
    </html>';
}
