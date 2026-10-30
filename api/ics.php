<?php
/** Download a programme event as an .ics calendar file. */
require __DIR__ . '/../includes/bootstrap.php';
$ev = row("SELECT p.*, f.deceased_name, f.slug FROM programme_events p JOIN funerals f ON f.id = p.funeral_id WHERE p.id = ? AND f.status = 'active'", [(int) input('id')]);
if (!$ev) { http_response_code(404); exit('Not found'); }
$tz = new DateTimeZone('Africa/Nairobi');
$start = new DateTime($ev['event_date'] . ' ' . $ev['start_time'], $tz);
$end = $ev['end_time'] ? new DateTime($ev['event_date'] . ' ' . $ev['end_time'], $tz) : (clone $start)->modify('+1 hour');
$esc = fn($s) => str_replace(["\\", ";", ",", "\r\n", "\n"], ["\\\\", "\;", "\\,", "\\n", "\\n"], (string) $s);
$loc = trim(($ev['venue'] ?? '') . ($ev['address'] ? ', ' . $ev['address'] : ''));
$desc = trim(($ev['description'] ?? '') . "\n" . programme_url($ev));
$lines = [
    'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Ndanda Tributes//EN', 'CALSCALE:GREGORIAN', 'BEGIN:VEVENT',
    'UID:prog-' . $ev['id'] . '@' . (parse_url(BASE_URL, PHP_URL_HOST) ?: 'ndanda'),
    'DTSTAMP:' . gmdate('Ymd\THis\Z'),
    'DTSTART:' . $start->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z'),
    'DTEND:' . $end->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z'),
    'SUMMARY:' . $esc($ev['title'] . ' – ' . $ev['deceased_name']),
    'LOCATION:' . $esc($loc),
    'DESCRIPTION:' . $esc($desc),
];
if ($ev['lat'] && $ev['lng']) $lines[] = 'GEO:' . $ev['lat'] . ';' . $ev['lng'];
$lines[] = 'END:VEVENT';
$lines[] = 'END:VCALENDAR';
while (ob_get_level()) ob_end_clean();
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="' . slugify($ev['title']) . '.ics"');
echo implode("\r\n", $lines);
