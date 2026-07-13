=== Plogins Recover - Abandoned Cart for WooCommerce ===
Contributors: motylanogha
Tags: woocommerce, abandoned cart, cart recovery, email, ecommerce
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Odzyskuj porzucone koszyki WooCommerce: wcześnie przechwyć adres e-mail, zapisz koszyk i wyślij bezpieczny link (jednym kliknięciem) do dokończenia zakupów.

== Description ==

Recover przechwytuje koszyki WooCommerce porzucone przez klientów i wysyła im bezpieczny link (jednym kliknięciem), który wstawia z powrotem wszystkie pozycje do koszyka, aby mogli dokończyć zakupy. Działa w całości w Twojej własnej witrynie: bez usług zewnętrznych, żadne dane nie opuszczają Twojego sklepu.

Ponieważ wszystko dzieje się na Twoim własnym serwerze, możesz dokładnie sprawdzić, co robi wtyczka. Pełny kod źródłowy znajduje się na https://github.com/wppoland/plogins-recover, gdzie możesz też zgłosić błąd lub zaproponować funkcję.

<strong>Jak to działa</strong>

1. Gdy tylko klient ma produkty w koszyku, Recover zapisuje prywatną migawkę tego koszyka.
2. Adres e-mail klienta jest przechwytywany wcześnie — automatycznie dla zalogowanych klientów oraz (za zgodą) z pola adresu e-mail w kasie w przypadku gości.
3. Jeśli zamówienie nie zostanie sfinalizowane w wybranym przez Ciebie oknie czasowym, koszyk zostaje oznaczony jako <strong>porzucony</strong>.
4. Przy następnym zaplanowanym uruchomieniu Recover wysyła wiadomość odzyskującą zawierającą bezpieczny link przywracający z tokenem.
5. Jedno kliknięcie tego linku ponownie wypełnia koszyk i odsyła klienta do kasy. Odzyskane koszyki są śledzone osobno, dzięki czemu widzisz swój współczynnik odzysku.

<strong>Kilka rzeczy, które warto wiedzieć</strong>

E-maile są wysyłane przez Twój własny mechanizm poczty WordPress (`wp_mail`), a dane koszyka znajdują się w jednej niestandardowej tabeli (`{prefix}_recover_carts`) w Twojej bazie danych. Nic nie jest wysyłane do usługi zewnętrznej.

Przechwytywanie adresu e-mail gościa następuje dopiero po zaznaczeniu przez klienta pola zgody, a jego treść możesz edytować lub całkowicie wyłączyć ten wymóg. Linki przywracające zawierają niemożliwy do odgadnięcia, 64-znakowy losowy token i nic więcej: brak identyfikatora klienta, brak adresu e-mail w adresie URL. Z ekranu koszyków możesz jednym kliknięciem usunąć wszystkie zapisane koszyki dla jednego adresu e-mail.

Po stronie implementacji cały wynik jest escapowany, a każde wejście sanityzowane, każdy formularz w panelu i żądanie AJAX jest weryfikowane nonce, a strony administracyjne wymagają uprawnienia `manage_woocommerce`. Wczesne przechwytywanie adresu e-mail korzysta z niewielkiego fragmentu czystego JavaScriptu (bez jQuery) ładowanego w stopce; proces odzyskiwania działa na cronie WordPressa i jest idempotentny, więc ponowne uruchomienie nigdy nie wyśle drugiego e-maila dla tego samego koszyka. Usunięcie wtyczki usuwa jej tabelę, kasuje jej dwie opcje i czyści zaplanowane zadanie.

<strong>Funkcje</strong>

* Automatyczne migawki koszyka przy każdej jego zmianie
* Wczesne przechwytywanie adresu e-mail dla zalogowanych klientów oraz (za zgodą) gości
* Konfigurowalne okno porzucenia i opóźnienie e-maila
* Bezpieczny link przywracający jednym kliknięciem (z tokenem), który ponownie wypełnia koszyk
* E-mail odzyskujący wysyłany według harmonogramu crona WordPressa przez `wp_mail`
* Lista koszyków porzuconych / odzyskanych / oczekujących z podsumowaniem współczynnika odzysku
* Konfigurowalny temat, nagłówek, treść i tekst przycisku wiadomości e-mail
* Przyjazne dla RODO pole zgody i usuwanie danych dla pojedynczego adresu e-mail jednym kliknięciem
* Zgodne z WooCommerce HPOS (Custom Order Tables) oraz blokami koszyka/kasy

== Installation ==

1. Zainstaluj i włącz WooCommerce (8.0 lub nowsze).
2. Zainstaluj Recover z katalogu wtyczek WordPress lub prześlij folder `recover` do `/wp-content/plugins/`.
3. Włącz wtyczkę na ekranie <strong>Wtyczki</strong>.
4. Wejdź w <strong>WooCommerce → Recover</strong>, aby ustawić czasy i dostosować e-mail; rozsądne ustawienia domyślne działają od razu.
5. Porzucone koszyki i Twój współczynnik odzysku pojawią się w <strong>WooCommerce → Recover Carts</strong>.

== Frequently Asked Questions ==

= Documentation and links =

* <strong>Dokumentacja</strong> - https://plogins.com/pl/plogins-recover/docs/
* <strong>Strona wtyczki</strong> - https://plogins.com/pl/plogins-recover/
* <strong>Kod źródłowy</strong> - https://github.com/wppoland/plogins-recover
* <strong>Zgłoszenia błędów i propozycje funkcji</strong> - https://github.com/wppoland/plogins-recover/issues


= Is Recover free? =
Tak. Recover jest darmowy i objęty licencją GPL.

= Does Recover require WooCommerce? =
Tak. Recover to rozszerzenie WooCommerce i wymaga WooCommerce 8.0 lub nowszego. Wyświetla powiadomienie w panelu i pozostaje nieaktywny, jeśli WooCommerce brakuje lub jest nieaktualne.

= How is the recovery email sent? =
Według harmonogramu crona WordPressa (domyślnie co godzinę). Każde uruchomienie oznacza koszyki nieaktywne dłużej niż Twoje okno jako porzucone, a następnie wysyła link odzyskujący do każdego porzuconego koszyka, dla którego nadszedł czas, używając mechanizmu poczty Twojej witryny (`wp_mail`). Proces jest idempotentny, więc nigdy nie wysyła podwójnie — każdy koszyk otrzymuje jeden e-mail odzyskujący.

= Is the restore link safe? =
Tak. Każdy koszyk ma 64-znakowy, kryptograficznie losowy token. Link przywracający zawiera tylko ten token: brak identyfikatora klienta, brak adresu e-mail, nic osobistego. Bez dokładnego tokenu koszyka nie da się przywrócić, więc nie ma ryzyka enumeracji ani IDOR.

= Does this comply with GDPR / consent requirements? =
Przechwytywanie adresu e-mail gościa następuje dopiero po zaznaczeniu przez klienta pola zgody (możesz edytować treść, a zgodę można uczynić wymaganą lub nie). Dane koszyka są przechowywane wyłącznie w Twojej własnej bazie danych i nigdy nie są wysyłane do podmiotów trzecich. W <strong>WooCommerce → Recover Carts</strong> możesz jednym kliknięciem usunąć wszystkie zapisane dane koszyka dla dowolnego adresu e-mail. Za politykę prywatności swojego sklepu odpowiadasz Ty.

= Where is cart data stored? =
W niestandardowej tabeli `{prefix}_recover_carts` w Twojej bazie danych WordPress. Nic nie jest wysyłane nigdzie indziej.

= How do I remove all plugin data? =
Usunięcie wtyczki z ekranu <strong>Wtyczki</strong> uruchamia procedurę dezinstalacji, która usuwa tabelę `{prefix}_recover_carts`, kasuje opcje `recover_settings` i `recover_db_version` oraz czyści zaplanowane zadanie odzyskiwania.


= Does this plugin work on WordPress Multisite? =

Tak. Ta wtyczka jest zgodna z WordPress Multisite. Włącz ją w całej sieci lub na poszczególnych witrynach; każda witryna zachowuje własne ustawienia i dane.

== External Services ==

Recover nie łączy się z żadnymi usługami zewnętrznymi. E-maile odzyskujące są wysyłane za pośrednictwem mechanizmu poczty WordPress Twojej witryny (`wp_mail`), a wszystkie dane koszyka pozostają w Twojej bazie danych WordPress.

== Screenshots ==

1. Lista porzuconych koszyków z liczbą oczekujących/porzuconych/odzyskanych oraz współczynnikiem odzysku.
2. E-mail odzyskujący z przyciskiem „Dokończ zamówienie” dostępnym jednym kliknięciem.

== Translations ==

Wtyczka Plogins Recover zawiera polskie, niemieckie i hiszpańskie tłumaczenia interfejsu wtyczki. Domena tekstowa to `plogins-recover`, więc pakiety językowe z WordPress.org mogą również nadpisywać lub rozszerzać te dołączone tłumaczenia.

== Changelog ==

= 1.0.2 =
* Dodano dołączone polskie, niemieckie i hiszpańskie tłumaczenia interfejsu wtyczki.

= 1.0.1 =
* Pierwsza stabilna wersja.

= 0.1.3 =
* Zmieniono nazwę na Plogins Recover dla WooCommerce, aby uzyskać bardziej charakterystyczną nazwę wtyczki.

= 0.1.2 =
* Akcja `recover/email_sent` po zaakceptowaniu e-maila odzyskującego przez wp_mail.
* Akcja `recover/cart_recovered`, gdy koszyk zostaje oznaczony jako odzyskany.
* `CartRepository::findById()` do wyszukiwania koszyka według klucza głównego.

= 0.1.1 =
* Sekwencje odzyskiwania z wieloma e-mailami: `recover/max_emails`, `recover/email_step_delay`,
  `recover/email/template_args` oraz trzeci argument `$step` w `recover/email/args`.
* Proces cron zwiększa `emails_sent` i planuje kolejne wiadomości na podstawie `last_email_at`.

= 0.1.0 =
* Pierwsze wydanie.
