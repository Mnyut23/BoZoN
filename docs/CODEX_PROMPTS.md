# Codex Prompt-Vorlagen (BoZoN)

## 1) Fix ein einzelnes Upstream-Issue
**Prompt:**
```
Aufgabe: Behebe Upstream-Issue <NR> – <ISSUE-TITEL>.
Quelle: https://github.com/broncowdd/BoZoN/issues/<NR>
Kontext/Leitplanken:
- Ziel: Minimaler, sicherer Fix ohne BC-Breaks.
- PHP 8.4-Kompatibilität; sichere JSON-/ZIP-/Pfad-Behandlung.
- Halte dich an den vorhandenen Stil (core/core.php etc.).
Vorgehen:
1) Ursache lokalisieren (Pfad + Code-Snippet).
2) Fix implementieren, Defense-in-Depth (Checks, Early-returns).
3) Kleine manuelle Testanleitung erstellen.
4) Commit-Message: "Fix: <kurz> (Closes broncowdd/BoZoN#<NR>)".
5) Branch: fix/ISSUE-<NR>-<kurzthema> und PR von meinem Fork nach Upstream erstellen.
Ausgabe: Änderungen als Diff + PR-Text.
```

## 2) Issues clustern (Batch-Auswahl)
**Prompt:**
```
Analysiere die offenen Issues: https://github.com/broncowdd/BoZoN/issues
Erstelle eine Tabelle mit: [#num, Titel, Kategorie (Bug|Security|Compat|Feature|Question), Schweregrad (hoch|mittel|niedrig), vermutete Dateien/Module].
Sortiere: Security/Crash oben, dann Compatibility.
Liefere die Top 15 als Start-Batch.
```

## 3) PR-Review auf meinem Fork
**Prompt:**
```
Reviewe meinen PR <URL>. Prüfe: Syntax, PHP 8.4, JSON/ZIP/Pfade, mögliche BC-Breaks.
Liste konkrete Code-Kommentare mit Pfad:Zeile und kurzen Vorschlägen.
```
