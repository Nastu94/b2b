<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nuovo Servizio Inserito</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #334155; line-height: 1.6; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        h1 { color: #0f172a; font-size: 24px; margin-bottom: 20px; }
        .highlight { font-weight: bold; color: #1e293b; }
        .btn { display: inline-block; background-color: #0f172a; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: bold; margin-top: 20px; }
        .footer { margin-top: 30px; font-size: 13px; color: #64748b; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Nuovo Servizio da Approvare!</h1>
        <p>Ciao Admin,</p>
        <p>Il fornitore <span class="highlight">{{ $vendorName }}</span> ha appena inserito un nuovo servizio sulla piattaforma.</p>
        
        <p><strong>Dettagli Servizio:</strong></p>
        <ul>
            <li><span class="highlight">Nome Servizio:</span> {{ $serviceName }}</li>
        </ul>

        <p>Prima di essere pubblicato e sincronizzato su PrestaShop, il servizio attende la tua ispezione e approvazione.</p>

        <div style="text-align: center;">
            <a href="{{ route('admin.dashboard') }}" class="btn">Vai alla Dashboard Admin</a>
        </div>

        <div class="footer">
            <p>Questa è una notifica automatica generata da Party Legacy Management Engine.</p>
        </div>
    </div>
</body>
</html>
