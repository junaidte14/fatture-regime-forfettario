# Fatture Regime Forfettario - WordPress Plugin

## 📋 Descrizione

Plugin WordPress professionale per la gestione delle fatture per freelancer italiani in regime forfettario. Compatibile con il sistema fiscale dell'Agenzia delle Entrate italiana.

## ✨ Funzionalità MVP

### ✅ Implementate
- **Gestione Clienti**
  - Classificazione automatica (IT/UE/Extra-UE)
  - Validazione P.IVA e Codice Fiscale
  - Campi per fatturazione elettronica (SDI, PEC)
  - Statistiche per cliente

- **Gestione Fatture**
  - Creazione e modifica fatture
  - Numerazione automatica progressiva
  - Gestione voci di fattura multiple
  - Calcolo automatico totali e ritenuta d'acconto
  - Stati fattura: Bozza, Inviata, Pagata, Scaduta, Annullata, Inviata SDI, Accettata SDI, Rifiutata SDI

- **Regime Forfettario**
  - Configurazione aliquota (5% o 15%)
  - Esenzione IVA
  - Gestione ritenuta d'acconto
  - Note personalizzabili

- **Dashboard**
  - Statistiche in tempo reale
  - Panoramica fatture e incassi
  - Fatture recenti

- **Impostazioni**
  - Dati aziendali
  - Configurazione fatturazione
  - Impostazioni regime forfettario
  - Notifiche email

### 🚀 Da Implementare (Prossime Fasi)
1. **Generazione XML FatturaPA**
   - Export formato XML compatibile SDI
   - Validazione schema XSD
   - Firma digitale
   - Invio automatico via PEC/SDI

2. **Report e Statistiche**
   - Report annuali/trimestrali
   - Analisi incassi
   - Export Excel/CSV

3. **Integrazione AI**
   - Riconoscimento OCR fatture
   - Suggerimenti automatici voci
   - Analisi predittiva incassi

4. **Funzionalità Avanzate**
   - Template fatture PDF personalizzabili
   - Promemoria pagamenti automatici
   - Integrazione con sistemi contabili
   - Multi-utente con ruoli

## 🛠️ Installazione

### Requisiti
- WordPress 6.0+
- PHP 7.4+
- MySQL 5.7+ o MariaDB 10.2+

### Installazione Manuale
1. Scarica il plugin
2. Carica la cartella `fatture-regime-forfettario` in `/wp-content/plugins/`
3. Attiva il plugin dal menu "Plugin" di WordPress
4. Vai su "Fatture RF" > "Impostazioni" per configurare i dati aziendali

### Primo Setup
1. **Impostazioni Generali**: Inserisci i tuoi dati aziendali (P.IVA, Codice Fiscale, indirizzo, SDI/PEC)
2. **Regime Forfettario**: Configura la tua aliquota (5% o 15%)
3. **Fatture**: Imposta il prefisso fatture e i termini di pagamento predefiniti
4. **Clienti**: Aggiungi i tuoi primi clienti

## 📊 Schema Database

### Tabella: `wp_frf_clients`
```sql
- id (PK)
- business_name
- vat_number
- tax_code
- email, pec_email
- address, city, province, postal_code
- country
- client_type (IT/EU/NON_EU)
- sdi_code
- created_at, updated_at
```

### Tabella: `wp_frf_invoices`
```sql
- id (PK)
- invoice_number (UNIQUE)
- invoice_date
- client_id (FK)
- subtotal, tax_rate, tax_amount, total
- withholding_tax, withholding_amount, net_to_pay
- payment_terms, payment_method
- notes
- status (draft/sent/paid/overdue/cancelled/submitted/accepted/rejected)
- paid_date, submission_date
- sdi_identifier, xml_file_path
- created_at, updated_at
```

### Tabella: `wp_frf_invoice_items`
```sql
- id (PK)
- invoice_id (FK)
- description
- quantity, unit_price, total
- sort_order
```

### Tabella: `wp_frf_status_history`
```sql
- id (PK)
- invoice_id (FK)
- old_status, new_status
- notes
- changed_by, changed_at
```

## 🔧 Estensioni e Personalizzazioni

### Aggiungere Nuovi Stati Fattura

```php
// In class-frf-invoice.php, modifica la colonna status nella tabella
// Aggiungi il nuovo stato nell'array in class-frf-admin-invoices.php

public static function get_status_badge($status) {
    $statuses = array(
        'your_new_status' => array(
            'label' => __('Nuovo Stato', 'fatture-rf'), 
            'class' => 'info'
        ),
        // ... altri stati
    );
}
```

### Creare Hook Personalizzati

```php
// Dopo la creazione fattura
do_action('frf_after_invoice_created', $invoice_id, $invoice_data);

// Prima della cancellazione
do_action('frf_before_invoice_deleted', $invoice_id);

// Cambio stato
do_action('frf_invoice_status_changed', $invoice_id, $old_status, $new_status);
```

### Aggiungere Campi Custom

```php
// In class-frf-invoice.php, aggiungi colonne alla tabella
// Modifica i metodi create() e update() per gestire i nuovi campi
// Aggiorna i form in admin/views/invoice-form.php
```

## 🚀 Prossimi Sviluppi: Generazione XML FatturaPA

### Implementazione Pianificata

```php
// Nuova classe: includes/class-frf-xml-generator.php

class FRF_XML_Generator {
    
    /**
     * Genera XML FatturaPA
     */
    public function generate($invoice_id) {
        $invoice = new FRF_Invoice();
        $data = $invoice->get($invoice_id);
        
        // Crea struttura XML secondo schema FatturaPA 1.2.2
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <p:FatturaElettronica xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" 
            versione="FPA12"/>');
        
        // FatturaElettronicaHeader
        $this->add_header($xml, $data);
        
        // FatturaElettronicaBody
        $this->add_body($xml, $data);
        
        return $xml->asXML();
    }
    
    private function add_header($xml, $data) {
        // Implementa DatiTrasmissione, CedentePrestatore, etc.
    }
    
    private function add_body($xml, $data) {
        // Implementa DatiGenerali, DatiBeniServizi, DatiPagamento
    }
    
    /**
     * Valida XML contro schema XSD
     */
    public function validate($xml_string) {
        $dom = new DOMDocument();
        $dom->loadXML($xml_string);
        return $dom->schemaValidate('path/to/Schema_FatturaPA_v1.2.2.xsd');
    }
}
```

### Integrazione SDI

```php
// Nuova classe: includes/class-frf-sdi-connector.php

class FRF_SDI_Connector {
    
    /**
     * Invia fattura a SDI via PEC
     */
    public function send_via_pec($invoice_id) {
        $xml = FRF_XML_Generator::generate($invoice_id);
        
        // Configura invio PEC
        $to = 'sdi01@pec.fatturapa.it';
        $subject = 'Fattura ' . $invoice_number;
        
        // Allega XML firmato
        // ...
    }
    
    /**
     * Verifica stato fattura su SDI
     */
    public function check_status($sdi_identifier) {
        // Controlla notifiche SDI
        // Aggiorna stato fattura
    }
}
```

## 🔐 Sicurezza

- ✅ Nonce verification su tutti i form
- ✅ Sanitizzazione input
- ✅ Prepared statements per query database
- ✅ Capability checks (manage_options)
- ✅ CSRF protection
- ✅ XSS prevention

## 🌍 Internazionalizzazione

Il plugin è pronto per traduzioni:

```bash
# Genera file .pot
wp i18n make-pot . languages/fatture-rf.pot

# Traduci in altre lingue
# Crea fatture-rf-it_IT.po/mo
```

## 📝 Licenza

GPL v2 or later

## 🤝 Supporto e Contributi

Per supporto o contributi:
- Email: junaidte14
- GitHub: [repository URL]

## 📚 Risorse Utili

- [Agenzia delle Entrate - Fatturazione Elettronica](https://www.agenziaentrate.gov.it/portale/web/guest/schede/comunicazioni/fatture-e-corrispettivi)
- [Specifiche Tecniche FatturaPA](https://www.fatturapa.gov.it/it/norme-e-regole/documentazione-fatturapa/)
- [Regime Forfettario](https://www.agenziaentrate.gov.it/portale/regime-forfetario)

---

**Versione**: 1.0.0  
**Autore**: Junaid Hassan  
**Ultimo aggiornamento**: Gennaio 2026