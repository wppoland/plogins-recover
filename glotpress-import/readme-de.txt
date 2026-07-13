=== Plogins Recover - Abandoned Cart for WooCommerce ===
Contributors: motylanogha
Tags: woocommerce, abandoned cart, cart recovery, email, ecommerce
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Stelle abgebrochene WooCommerce-Warenkörbe wieder her: erfasse die E-Mail-Adresse früh, speichere den Warenkorb und sende einen sicheren Ein-Klick-Link, um den Checkout abzuschließen.

== Description ==

Recover erfasst WooCommerce-Warenkörbe, die Kundschaft zurücklässt, und schickt ihnen einen sicheren Ein-Klick-Link, der jeden Artikel direkt zurück in den Warenkorb legt, damit sie den Checkout abschließen können. Es läuft vollständig auf deiner eigenen Website: kein Drittanbieterdienst, keine Daten verlassen deinen Shop.

Da alles auf deinem eigenen Server passiert, kannst du genau nachlesen, was es tut. Der vollständige Quellcode liegt unter https://github.com/wppoland/plogins-recover, wo du auch einen Fehler melden oder eine Funktion vorschlagen kannst.

<strong>So funktioniert es</strong>

1. Sobald ein Kunde Artikel im Warenkorb hat, speichert Recover eine private Momentaufnahme dieses Warenkorbs.
2. Die Kunden-E-Mail wird früh erfasst – automatisch bei eingeloggten Kunden und (mit Einwilligung) aus dem E-Mail-Feld an der Kasse bei Gästen.
3. Wird der Checkout nicht innerhalb eines von dir gewählten Zeitfensters abgeschlossen, wird der Warenkorb als <strong>abgebrochen</strong> markiert.
4. Beim nächsten geplanten Lauf sendet Recover eine Wiederherstellungs-E-Mail mit einem sicheren, tokenisierten Wiederherstellungslink.
5. Ein Klick auf diesen Link füllt den Warenkorb wieder und schickt den Kunden zurück zur Kasse. Wiederhergestellte Warenkörbe werden separat erfasst, sodass du deine Wiederherstellungsrate siehst.

<strong>Ein paar Dinge, die du wissen solltest</strong>

E-Mails werden über deinen eigenen WordPress-Mailer (`wp_mail`) versendet, und die Warenkorbdaten liegen in einer einzigen eigenen Tabelle (`{prefix}_recover_carts`) in deiner Datenbank. Nichts wird an einen externen Dienst gesendet.

Die E-Mail-Erfassung bei Gästen erfolgt erst, nachdem der Kunde ein Einwilligungs-Kontrollkästchen angekreuzt hat, und du kannst den Wortlaut bearbeiten oder die Pflicht abschalten. Wiederherstellungslinks tragen einen nicht erratbaren, 64-stelligen Zufallstoken und sonst nichts: keine Kunden-ID, keine E-Mail-Adresse in der URL. Vom Warenkorb-Bildschirm aus kannst du mit einem Klick alle gespeicherten Warenkörbe für eine einzelne E-Mail-Adresse löschen.

Auf der Implementierungsseite wird jede Ausgabe escaped und jede Eingabe bereinigt, jedes Adminformular und jede AJAX-Anfrage wird per Nonce geprüft, und die Adminseiten benötigen die Berechtigung `manage_woocommerce`. Die frühe E-Mail-Erfassung nutzt ein kleines Snippet aus reinem JavaScript (kein jQuery), das in der Fußzeile geladen wird; der Wiederherstellungs-Worker läuft über den WordPress-Cron und ist idempotent, sodass ein erneuter Lauf nie eine zweite E-Mail für denselben Warenkorb sendet. Beim Löschen des Plugins wird seine Tabelle entfernt, seine beiden Optionen werden gelöscht und die geplante Aufgabe wird entfernt.

<strong>Funktionen</strong>

* Automatische Warenkorb-Momentaufnahmen bei jeder Änderung des Warenkorbs
* Frühe E-Mail-Erfassung für eingeloggte Kunden und (einwilligungspflichtige) Gäste
* Konfigurierbares Abbruch-Zeitfenster und E-Mail-Verzögerung
* Sicherer, tokenisierter Ein-Klick-Wiederherstellungslink, der den Warenkorb wieder füllt
* Wiederherstellungs-E-Mail, die über einen WordPress-Cron-Zeitplan mit `wp_mail` gesendet wird
* Liste abgebrochener / wiederhergestellter / ausstehender Warenkörbe mit einer Zusammenfassung der Wiederherstellungsrate
* Anpassbarer Betreff, Überschrift, Inhalt und Button-Text der E-Mail
* DSGVO-freundliches Einwilligungs-Kontrollkästchen und Ein-Klick-Datenlöschung pro E-Mail-Adresse
* Kompatibel mit WooCommerce HPOS (Custom Order Tables) und Warenkorb-/Kassen-Blöcken

== Installation ==

1. Installiere und aktiviere WooCommerce (8.0 oder höher).
2. Installiere Recover aus dem WordPress-Plugin-Verzeichnis oder lade den Ordner `recover` nach `/wp-content/plugins/` hoch.
3. Aktiviere das Plugin über den Bildschirm <strong>Plugins</strong>.
4. Öffne <strong>WooCommerce → Recover</strong>, um dein Timing festzulegen und die E-Mail anzupassen; sinnvolle Standardwerte funktionieren sofort.
5. Abgebrochene Warenkörbe und deine Wiederherstellungsrate erscheinen unter <strong>WooCommerce → Recover Carts</strong>.

== Frequently Asked Questions ==

= Documentation and links =

* <strong>Dokumentation</strong> - https://plogins.com/de/plogins-recover/docs/
* <strong>Plugin-Seite</strong> - https://plogins.com/de/plogins-recover/
* <strong>Quellcode</strong> - https://github.com/wppoland/plogins-recover
* <strong>Fehlerberichte und Funktionswünsche</strong> - https://github.com/wppoland/plogins-recover/issues


= Is Recover free? =
Ja. Recover ist kostenlos und unter der GPL lizenziert.

= Does Recover require WooCommerce? =
Ja. Recover ist eine WooCommerce-Erweiterung und erfordert WooCommerce 8.0 oder höher. Es zeigt einen Admin-Hinweis und bleibt inaktiv, wenn WooCommerce fehlt oder veraltet ist.

= How is the recovery email sent? =
Über einen WordPress-Cron-Zeitplan (standardmäßig stündlich). Jeder Lauf markiert Warenkörbe, die länger als dein Zeitfenster inaktiv waren, als abgebrochen und sendet dann einen Wiederherstellungslink an jeden fälligen abgebrochenen Warenkorb – über den Mailer deiner eigenen Website (`wp_mail`). Der Worker ist idempotent, versendet also nie doppelt, sodass jeder Warenkorb genau eine Wiederherstellungs-E-Mail erhält.

= Is the restore link safe? =
Ja. Jeder Warenkorb hat einen 64-stelligen, kryptografisch zufälligen Token. Der Wiederherstellungslink enthält nur diesen Token: keine Kunden-ID, keine E-Mail-Adresse, nichts Persönliches. Ohne den genauen Token lässt sich ein Warenkorb nicht wiederherstellen, es gibt also kein Enumerations- oder IDOR-Risiko.

= Does this comply with GDPR / consent requirements? =
Die E-Mail-Erfassung bei Gästen erfolgt erst, nachdem der Kunde ein Einwilligungs-Kontrollkästchen angekreuzt hat (du kannst den Wortlaut bearbeiten, und die Einwilligung kann verpflichtend sein oder nicht). Warenkorbdaten werden nur in deiner eigenen Datenbank gespeichert und nie an Dritte gesendet. Unter <strong>WooCommerce → Recover Carts</strong> kannst du mit einem Klick alle gespeicherten Warenkorbdaten für eine beliebige E-Mail-Adresse löschen. Für die Datenschutzerklärung deines Shops bleibst du selbst verantwortlich.

= Where is cart data stored? =
In einer eigenen Tabelle `{prefix}_recover_carts` in deiner WordPress-Datenbank. Nichts wird sonst irgendwohin gesendet.

= How do I remove all plugin data? =
Das Löschen des Plugins über den Bildschirm <strong>Plugins</strong> führt die Deinstallationsroutine aus, die die Tabelle `{prefix}_recover_carts` entfernt, die Optionen `recover_settings` und `recover_db_version` löscht und die geplante Wiederherstellungsaufgabe entfernt.


= Does this plugin work on WordPress Multisite? =

Ja. Dieses Plugin ist mit WordPress Multisite kompatibel. Aktiviere es netzwerkweit oder auf einzelnen Websites; jede Website behält ihre eigenen Einstellungen und Daten.

== External Services ==

Recover stellt keine Verbindung zu externen Diensten her. Wiederherstellungs-E-Mails werden über den WordPress-Mailer deiner eigenen Website (`wp_mail`) gesendet, und alle Warenkorbdaten bleiben in deiner WordPress-Datenbank.

== Screenshots ==

1. Liste der abgebrochenen Warenkörbe mit der Anzahl der ausstehenden/abgebrochenen/wiederhergestellten Warenkörbe und der Wiederherstellungsrate.
2. Die Wiederherstellungs-E-Mail mit ihrem Ein-Klick-Button „Meine Bestellung abschließen“.

== Translations ==

Plogins Recover enthält deutsche, polnische und spanische Übersetzungen für die Plugin-Oberfläche. Die Textdomain ist `plogins-recover`, sodass Sprachpakete von WordPress.org diese mitgelieferten Übersetzungen ebenfalls überschreiben oder erweitern können.

== Changelog ==

= 1.0.2 =
* Mitgelieferte deutsche, polnische und spanische Übersetzungen für die Plugin-Oberfläche hinzugefügt.

= 1.0.1 =
* Erste stabile Version.

= 0.1.3 =
* Für einen eindeutigeren Plugin-Namen in Plogins Recover für WooCommerce umbenannt.

= 0.1.2 =
* Aktion `recover/email_sent`, nachdem eine Wiederherstellungs-E-Mail von wp_mail akzeptiert wurde.
* Aktion `recover/cart_recovered`, wenn ein Warenkorb als wiederhergestellt markiert wird.
* `CartRepository::findById()` für die Warenkorbsuche nach Primärschlüssel.

= 0.1.1 =
* Wiederherstellungssequenzen für mehrere E-Mails: `recover/max_emails`, `recover/email_step_delay`,
  `recover/email/template_args` und ein drittes `$step`-Argument auf `recover/email/args`.
* Cron-Worker erhöht `emails_sent` und plant Follow-ups ab `last_email_at`.

= 0.1.0 =
* Erstveröffentlichung.
