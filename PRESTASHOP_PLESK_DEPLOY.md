# Deploy PrestaShop via Plesk

Versione modulo da ottenere: `bookingbridge 1.5.1`
Versione tema da ottenere: `partylegacy 1.0.2`

Il file `PRESTASHOP_FILES_PLESK.txt` contiene l'elenco operativo, con un percorso per riga. I percorsi sono relativi alla radice di PrestaShop. Il manifest comprende tutte le modifiche accumulate durante l'audit, incluse le ultime correzioni a parametri evento, chat, sicurezza HTML e classificazione delle risposte API.

## File del modulo da caricare

1. `modules/bookingbridge/bookingbridge.php`
2. `modules/bookingbridge/config.xml`
3. `modules/bookingbridge/config_it.xml`
4. `modules/bookingbridge/classes/BookingBridgeApiClassifier.php`
5. `modules/bookingbridge/classes/BookingBridgeClient.php`
6. `modules/bookingbridge/classes/BookingBridgeConfirmationWorker.php`
7. `modules/bookingbridge/classes/BookingBridgeHelper.php`
8. `modules/bookingbridge/classes/BookingBridgeHmac.php`
9. `modules/bookingbridge/classes/BookingBridgeInstaller.php`
10. `modules/bookingbridge/classes/BookingBridgeOrderConfirm.php`
11. `modules/bookingbridge/classes/BookingBridgeReleaseService.php`
12. `modules/bookingbridge/classes/BookingBridgeRemoteImageDownloader.php`
13. `modules/bookingbridge/classes/CartSelectionRepository.php`
14. `modules/bookingbridge/classes/VendorProductManager.php`
15. `modules/bookingbridge/classes/hooks/BookingBridgeUIHooks.php`
16. `modules/bookingbridge/classes/hooks/BookingBridgeVendorDisplayHooks.php`
17. `modules/bookingbridge/controllers/front/api.php`
18. `modules/bookingbridge/controllers/front/availability.php`
19. `modules/bookingbridge/controllers/front/chat.php`
20. `modules/bookingbridge/controllers/front/cron.php`
21. `modules/bookingbridge/controllers/front/search.php`
22. `modules/bookingbridge/controllers/front/vendor.php`
23. `modules/bookingbridge/controllers/front/webhook.php`
24. `modules/bookingbridge/upgrade/upgrade-1.1.2.php`
25. `modules/bookingbridge/upgrade/upgrade-1.3.0.php`
26. `modules/bookingbridge/upgrade/upgrade-1.4.0.php`
27. `modules/bookingbridge/upgrade/upgrade-1.4.1.php`
28. `modules/bookingbridge/upgrade/upgrade-1.5.0.php`
29. `modules/bookingbridge/upgrade/upgrade-1.5.1.php`
30. `modules/bookingbridge/views/js/bookingbridge-messages-page.js`
31. `modules/bookingbridge/views/templates/front/availability.tpl`
32. `modules/bookingbridge/views/templates/front/search_results.tpl`
33. `modules/bookingbridge/views/templates/front/vendor.tpl`
34. `modules/bookingbridge/views/templates/hook/vendor_profile_hook.tpl`

## File del tema da caricare

1. `themes/partylegacy/config/theme.yml`
2. `themes/partylegacy/templates/_partials/header.tpl`

Non caricare `modules/bookingbridge_v4.zip`: contiene una vecchia versione e non rappresenta il modulo corrente.

## Ordine sicuro di pubblicazione

1. Attivare la manutenzione e creare da Plesk un backup completo di file e database, sia Laravel sia PrestaShop.
2. Verificare gli ID reali delle categorie PrestaShop di produzione prima di valorizzare `categories.prestashop_category_id` in Laravel. Gli ID locali non vanno copiati alla cieca.
3. Configurare i segreti descritti sotto. Durante il solo passaggio tra le due applicazioni si può tenere Laravel in `BOOKING_BRIDGE_HMAC_MODE=optional`; dopo il test delle firme impostare `required`.
4. Pubblicare Laravel, installare le dipendenze di produzione, compilare gli asset, eseguire `php artisan migrate --force`, `php artisan optimize:clear`, `php artisan config:cache` e `php artisan queue:restart`.
5. Caricare via Plesk tutti i 34 file del modulo, mantenendo esattamente le cartelle indicate nel manifest.
6. Nel back office PrestaShop aggiornare Booking Bridge fino alla versione 1.5.1. Non disinstallare il modulo: la disinstallazione eliminerebbe le tabelle del bridge.
7. Verificare che il modulo sia attivo e in versione 1.5.1. Se l'aggiornamento non compare, svuotare la cache PrestaShop e riaprire Gestione moduli.
8. Aprire Configura sul modulo, salvare URL e chiavi e lasciare attivo `Usa dati server per selezioni`.
9. Caricare i 2 file del tema e svuotare la cache da Parametri avanzati, Prestazioni.
10. Configurare in Plesk un'attività ogni 5 minuti verso `https://DOMINIO_PRESTASHOP/module/bookingbridge/cron?token=TOKEN_CRON` e verificare una risposta JSON positiva.
11. Verificare che il worker Laravel sia attivo e supervisionato, che l'SMTP reale funzioni e che i log siano scrivibili.
12. Eseguire i controlli bloccanti sotto. Solo dopo esito positivo togliere la manutenzione e impostare definitivamente `BOOKING_BRIDGE_HMAC_MODE=required`, `BOOKING_BRIDGE_DISTANCE_MODE=enforce`, `BOOKING_BRIDGE_GEOCODING_FALLBACK_MODE=strict` e `BOOKING_BRIDGE_ALLOW_EXPIRED_HOLD_REACQUIRE=false`.

## Configurazione e chiavi

Non inserire chiavi reali in questo documento. Usare segreti casuali di almeno 32 caratteri.

- PrestaShop `BOOKINGBRIDGE_API_BASE_URL`: URL pubblico HTTPS di Laravel, senza `/api` finale.
- PrestaShop `BOOKINGBRIDGE_API_KEY`: uguale alle chiavi Laravel previste per le chiamate bridge.
- PrestaShop `BOOKINGBRIDGE_OUTBOUND_HMAC_KEY`: uguale a Laravel `BOOKING_BRIDGE_HMAC_SECRET_INBOUND`.
- PrestaShop `BOOKINGBRIDGE_INBOUND_HMAC_KEY`: uguale a Laravel `BOOKING_BRIDGE_HMAC_SECRET_OUTBOUND`.
- PrestaShop `BOOKINGBRIDGE_CRON_TOKEN`: token casuale dedicato e diverso dalle chiavi API/HMAC.
- Laravel `PRESTASHOP_API_URL`: `https://DOMINIO_PRESTASHOP/module/bookingbridge/api`.
- Laravel `PRESTASHOP_WEBHOOK_URL`: `https://DOMINIO_PRESTASHOP/module/bookingbridge/webhook`.
- Laravel: `APP_ENV=production`, `APP_DEBUG=false`, URL pubblici HTTPS e credenziali SMTP reali.

## Verifiche bloccanti prima della riapertura

- Registrazione vendor completa di logo e documento; approvazione account, documento e servizi da amministratore.
- Ricerca con città, regione, data, ospiti, tipo evento, categoria, modalità servizio e offering; tutti i parametri devono restare coerenti tra ricerca, disponibilità e carrello.
- Un carrello non deve accettare servizi con data, città o numero ospiti differenti.
- `single_resource`: un hold su vendor, slot e data deve bloccare anche un offering differente.
- `multiple_by_offering`: sullo stesso vendor, slot e data due offering differenti devono essere prenotabili; lo stesso offering non deve essere duplicabile.
- Capienza: un servizio con `max_guests=80` non deve comparire per una ricerca da 100 ospiti.
- Prezzo: verificare prezzo base, regole per giorno/data/distanza/anticipo/ospiti e totale del carrello.
- Ordine pagato: deve generare una prenotazione Laravel per ciascun servizio; un ordine non pagato non deve generarla.
- Risposta vendor: conferma e rifiuto devono aggiornare lo stato previsto e produrre le notifiche configurate.
- Cron/outbox: nessun record deve restare bloccato in `PROCESSING`; verificare retry e idempotenza.
- Catalogo vendor: attivazione, modifica e disattivazione devono aggiornare soltanto il prodotto del vendor proprietario.
- Homepage: finché il valore reale non supera 60, il badge partner deve mostrare il totale reale più 100 e il badge città il totale reale più 30; oltre 60 deve mostrare il dato reale.
- Chat: apertura conversazione, invio, polling incrementale, conteggi non letti e autorizzazioni cliente/vendor/admin. Dalla scheda prodotto deve essere inviato il `vendor_account_id` Laravel, mai l'ID prodotto PrestaShop.

## Avvertenze operative

- Bonifico e assegno non sono adatti a hold di 15 minuti: la prenotazione Laravel nasce soltanto quando lo stato PrestaShop ha `paid=1`. In produzione preferire un pagamento online immediato oppure definire una procedura esplicita per pagamenti tardivi e rimborsi.
- Il rifiuto del vendor richiede una procedura di rimborso/stato ordine coerente: non presumere che il solo cambio stato Laravel esegua automaticamente un rimborso nel gateway.
- La sincronizzazione delle immagini vendor richiede URL Laravel pubblici e raggiungibili da PrestaShop; gli URL `localhost` sono correttamente rifiutati dai controlli SSRF.
- Worker Laravel, cron PrestaShop e SMTP sono dipendenze operative obbligatorie, non opzionali.

## Rollback

Se aggiornamento modulo, migrazioni o test slot falliscono, mantenere la manutenzione e ripristinare insieme file e database dal backup Plesk creato immediatamente prima del deploy. Il solo ripristino dei file non annulla le modifiche di schema delle versioni 1.5.0 e 1.5.1.
