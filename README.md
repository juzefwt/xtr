Narzędzia pomocnicze dla MEAD-pl
===

- skrypt `process.php` wstępnie przetwarza streszczane dokumenty za pomocą [WCRFT](http://nlp.pwr.wroc.pl/redmine/projects/wcrft/wiki)
- w katalogu `app` znajduje się prosta aplikacja przeglądarkowa oparta o [framework Silex](https://github.com/silexphp/Silex), demonstrująca działanie [MEAD-pl](https://github.com/juzefwt/mead-pl)

## Instalacja

- najpierw należy zainstalować [MEAD-pl](https://github.com/juzefwt/mead-pl) oraz [WCRFT](http://nlp.pwr.wroc.pl/redmine/projects/wcrft/wiki) ze wszystkimi wymaganymi zależnościami
- do działania aplikacji demo konieczne jest skonfigurowanie vhosta na dowolnym serwerze WWW

## Użycie

Skrypt `process.php` może być użyty w następujący sposób:
```
php process.php /sciezka/do/dokument.txt
```
Parametrem skryptu może być zarówno ścieżka do pojedynczego pliku jak i całego katalogu z dokumentami.
Dokumenty przeznaczone do streszczania muszą mieć rozszerzenie .txt.

Aplikacja demonstracyjna do działania wymaga, aby użytkownik sytemo, na którego prawach działa serwer WWW, miał pełny dostęp do katalogu `/tmp/xtr`.