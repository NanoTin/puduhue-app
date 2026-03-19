<?php
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>

<style>
    .bi-report-wrapper {
        min-height: calc(100vh - 180px);
        background: linear-gradient(135deg, #0b132b 0%, #1c2541 45%, #3a506b 100%);
        padding: 24px 12px 32px;
        color: #f7f9fb;
    }
    .bi-report-card {
        max-width: 1320px;
        margin: 0 auto;
        background: #0f172a;
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 16px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
        overflow: hidden;
    }
    .bi-report-header {
        padding: 24px 28px;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        background: radial-gradient(circle at 20% 20%, rgba(58, 80, 107, 0.3), transparent 55%);
    }
    .bi-report-header h3 {
        margin: 0;
        font-weight: 700;
        letter-spacing: 0.2px;
        color: #ffffff;
    }
    .bi-report-header p {
        margin: 4px 0 0 0;
        color: #d5deef;
        font-size: 14px;
    }
    .bi-report-actions a {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        background: #14b8a6;
        color: #0b132b;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 600;
        transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.2s ease;
        box-shadow: 0 10px 24px rgba(20, 184, 166, 0.35);
    }
    .bi-report-actions a:hover {
        background: #0ea5e9;
        color: #0b132b;
        transform: translateY(-1px);
        box-shadow: 0 16px 32px rgba(14, 165, 233, 0.32);
    }
    .bi-report-frame {
        width: 100%;
        min-height: 820px;
        border: none;
        display: block;
        background: #0f172a;
    }
    @media (max-width: 1024px) {
        .bi-report-header {
            flex-direction: column;
            align-items: flex-start;
        }
        .bi-report-frame {
            min-height: 80vh;
        }
        .bi-report-wrapper {
            padding: 24px 12px;
        }
    }
</style>

<div class="bi-report-wrapper">
    <div class="bi-report-card">
        <div class="bi-report-header">
            <div>
                <h3>Power BI - Control de margen de Producción de Leche</h3>
                <p>Indicadores clave.</p>
            </div>
            <div class="bi-report-actions">
                <a href="https://app.powerbi.com/view?r=eyJrIjoiY2FkMTY0ZTgtNzBmZS00YWNjLWEyOWYtOWUwZGFiNmU5ZTlmIiwidCI6Ijg4MTJkNDkxLWMxNTEtNGNmNC1hZDAzLTc2NzFmNTdhNGEyMyIsImMiOjR9"
                   target="_blank" rel="noopener noreferrer">
                    Abrir en Power BI
                </a>
            </div>
        </div>
        <iframe
            class="bi-report-frame"
            title="Control margen produccion leche Puduhue"
            width="100%"
            height="820"
            src="https://app.powerbi.com/view?r=eyJrIjoiY2FkMTY0ZTgtNzBmZS00YWNjLWEyOWYtOWUwZGFiNmU5ZTlmIiwidCI6Ijg4MTJkNDkxLWMxNTEtNGNmNC1hZDAzLTc2NzFmNTdhNGEyMyIsImMiOjR9"
            frameborder="0"
            allowfullscreen="true">
        </iframe>
    </div>
</div>

<?php if (!$isPartial) { require 'footer.php'; } ?>
