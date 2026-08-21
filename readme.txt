

ALTER TABLE wp_se_events
ADD exclude_ranges LONGTEXT NULL;



Installation auf dem Server:

FE short cuts:
        
        [fe_next_events] -> shortcut in dem tortuga widgets seitenleiste
        [se_events_modern] -> shortcut auf der Veranstaltungsseite


Links CTP:
        Einstellungen → Permalinks-> Änderungen speichern


DB bereinigung



-----------------------------------
SELECT
    e.event_id,
    p.ID AS post_id,
    p.post_title,
    e.event_end
FROM wp_se_events AS e
LEFT JOIN wp_posts AS p
    ON p.ID = e.post_id
WHERE e.event_type = 'recurrence'
  AND e.event_end < NOW();

---
DELETE e, p
FROM wp_se_events AS e
LEFT JOIN wp_posts AS p
    ON p.ID = e.post_id
WHERE e.event_type = 'recurrence'
  AND e.event_end < NOW();


-------------------------------



SELECT
    e.event_id,
    p.ID AS post_id,
    p.post_title,
    e.event_end
FROM wp_se_events AS e
LEFT JOIN wp_posts AS p
    ON p.ID = e.post_id
WHERE e.event_type = 'event'
  AND e.event_end < NOW();
----
DELETE e, p
FROM wp_se_events AS e
LEFT JOIN wp_posts AS p
    ON p.ID = e.post_id
WHERE e.event_type = 'event'
  AND e.event_end < NOW();

----------------------

Änderungen:




2026_06_16:

ok      cpt child themes single-se_event.php hinzugefügt 
ok      Ferienzeiten integriert
        rpe und se events mit post versehen
ok      Ferienzeiten vier zeit segmente berücksichtigen
        rpe Änderung up-date im rpe ui
ok      ALTER TABLE wp_se_events ADD exclude_ranges LONGTEXT NULL; beim db copy


2026_06_03
ok       beim löschen von rpe auch die dazugehörigen rcp löschen

2025_05_26
ok      Paginierung FE überprüfen
ok      neue event mit daily morgen &übermorgen erzeugen
ok      hide im ui die box event struktur
ok      se datum eingabe maske datum & uhrzeit mit js start ende verknüpfen
ok      bei hauptliste vergangenheit -> desc 

2026_04_20
zeile 710  #delete_post_meta($id, '_previous_status'); überprüfen  class_se_events_list_table

2026-04-15
DB wp_se_events -> event_type == event_old records überprüfen nach gleicher event_id und Post ID

2026-04-07
veranstaltungen:
Einzeln- und Recurrence Event vergangene filtern  
mehrfach aktion in den Papierkorb hinzufügen

        bei Berabeiten
ok      bei veröffentlichung -> papierkorb hinzufügen

ok      bei recurrence eltern alle frequenzen hinzufügen (z.B. monat, zweiwöchig ... wie auf  hp)

ok      Beim verschieben in den Papierkorb den letzten Status in dB speichern und bei zuück wieder.
