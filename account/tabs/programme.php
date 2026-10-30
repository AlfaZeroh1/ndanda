<?php
if ($mode === 'post') {
    $act = input('act');
    if ($act === 'save_event') {
        $lat = input('lat') !== '' ? (float) input('lat') : null;
        $lng = input('lng') !== '' ? (float) input('lng') : null;
        if ($lat !== null && ($lat < -90 || $lat > 90 || $lng === null || $lng < -180 || $lng > 180)) throw new RuntimeException('The GPS coordinates are not valid.');
        if (!input('event_date') || !input('start_time') || trim(input('title')) === '') throw new RuntimeException('Date, start time and title are required.');
        $data = [
            'event_date' => input('event_date'), 'start_time' => input('start_time'), 'end_time' => input('end_time') ?: null,
            'title' => mb_substr(input('title'), 0, 160), 'description' => input('description') ?: null,
            'venue' => mb_substr(input('venue'), 0, 160) ?: null, 'address' => mb_substr(input('address'), 0, 255) ?: null,
            'lat' => $lat, 'lng' => $lng, 'contact_name' => mb_substr(input('contact_name'), 0, 120) ?: null,
            'contact_phone' => mb_substr(input('contact_phone'), 0, 30) ?: null, 'sort' => (int) input('sort'),
        ];
        $eid = (int) input('eid');
        if ($eid) update('programme_events', $data, 'id = ? AND funeral_id = ?', [$eid, $fid]);
        else insert('programme_events', $data + ['funeral_id' => $fid]);
        flash('success', 'Programme event saved.');
    } elseif ($act === 'delete_event') {
        q('DELETE FROM programme_events WHERE id = ? AND funeral_id = ?', [(int) input('eid'), $fid]);
        flash('success', 'Event removed.');
    } elseif ($act === 'save_oos') {
        if (trim(input('item')) === '') throw new RuntimeException('Enter the programme item.');
        $data = ['time_label' => mb_substr(input('time_label'), 0, 30) ?: null, 'item' => mb_substr(input('item'), 0, 200), 'person' => mb_substr(input('person'), 0, 160) ?: null, 'details' => input('details') ?: null, 'sort' => (int) input('sort')];
        $oid = (int) input('oid');
        if ($oid) update('order_of_service', $data, 'id = ? AND funeral_id = ?', [$oid, $fid]);
        else insert('order_of_service', $data + ['funeral_id' => $fid, 'sort' => (int) val('SELECT COALESCE(MAX(sort),0)+1 FROM order_of_service WHERE funeral_id = ?', [$fid])]);
        flash('success', 'Order of service updated.');
    } elseif ($act === 'delete_oos') {
        q('DELETE FROM order_of_service WHERE id = ? AND funeral_id = ?', [(int) input('oid'), $fid]);
        flash('success', 'Item removed.');
    }
    return;
}
$events = rows('SELECT * FROM programme_events WHERE funeral_id = ? ORDER BY event_date, start_time, sort', [$fid]);
$oos = rows('SELECT * FROM order_of_service WHERE funeral_id = ? ORDER BY sort, id', [$fid]);
$extraHead = '';
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div><h2 class="h4 mb-0">Programme & venues</h2><div class="small-muted">Add every step of the day with its exact location. Mourners get one-tap directions.</div></div>
  <div class="d-flex gap-2">
    <a href="<?= e(programme_url($f)) ?>" target="_blank" class="btn btn-outline-ink rounded-pill btn-sm"><i class="bi bi-eye"></i> Preview</a>
    <button class="btn btn-gold rounded-pill" data-bs-toggle="modal" data-bs-target="#eventModal" data-event="{}"><i class="bi bi-plus-lg"></i> Add event</button>
  </div>
</div>

<?php if (!$events): ?>
  <div class="card-soft empty mb-4"><i class="bi bi-signpost-split"></i>No events yet. Typical programme: body viewing → cortege → church service → burial → reception.</div>
<?php else: ?>
  <div class="card-soft mb-4 table-responsive">
    <table class="table align-middle mb-0">
      <thead><tr class="small text-muted"><th>When</th><th>Event</th><th>Venue</th><th>GPS</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($events as $ev): ?>
        <tr>
          <td class="text-nowrap"><strong><?= e(date('g:i A', strtotime($ev['start_time']))) ?></strong><div class="small-muted"><?= e(fmt_date($ev['event_date'], 'D j M')) ?></div></td>
          <td><?= e($ev['title']) ?><?php if ($ev['contact_name']): ?><div class="small-muted"><i class="bi bi-person"></i> <?= e($ev['contact_name']) ?> <?= e($ev['contact_phone']) ?></div><?php endif; ?></td>
          <td><?= e($ev['venue']) ?><div class="small-muted"><?= e($ev['address']) ?></div></td>
          <td><?= $ev['lat'] !== null ? '<a target="_blank" rel="noopener" href="https://www.google.com/maps?q=' . e($ev['lat'] . ',' . $ev['lng']) . '" class="badge text-bg-success text-decoration-none"><i class="bi bi-geo-alt"></i> Pinned</a>' : '<span class="badge text-bg-warning">No pin</span>' ?></td>
          <td class="text-end text-nowrap">
            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#eventModal" data-event="<?= e(json_encode($ev)) ?>"><i class="bi bi-pencil"></i></button>
            <form method="post" class="d-inline" data-confirm="Remove this event?"><?= csrf_field() ?><input type="hidden" name="act" value="delete_event"><input type="hidden" name="eid" value="<?= (int) $ev['id'] ?>"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-xl-7">
    <div class="card-soft p-4">
      <h3 class="h5">Order of service</h3>
      <p class="small-muted">Hymns, readings, eulogy, tributes and speakers — shown on the programme and printed booklet.</p>
      <?php foreach ($oos as $o): ?>
        <form method="post" class="row g-2 align-items-center border-bottom py-2">
          <?= csrf_field() ?><input type="hidden" name="act" value="save_oos"><input type="hidden" name="oid" value="<?= (int) $o['id'] ?>">
          <div class="col-3 col-md-2"><input name="time_label" class="form-control form-control-sm" value="<?= e($o['time_label']) ?>" placeholder="Time"></div>
          <div class="col-9 col-md-4"><input name="item" class="form-control form-control-sm" value="<?= e($o['item']) ?>" required></div>
          <div class="col-6 col-md-3"><input name="person" class="form-control form-control-sm" value="<?= e($o['person']) ?>" placeholder="Led by"></div>
          <div class="col-2 col-md-1"><input type="number" name="sort" class="form-control form-control-sm" value="<?= (int) $o['sort'] ?>" title="Order"></div>
          <div class="col-4 col-md-2 text-end text-nowrap">
            <button class="btn btn-sm btn-outline-success"><i class="bi bi-check2"></i></button>
            <button class="btn btn-sm btn-outline-danger" name="act" value="delete_oos" formnovalidate onclick="return confirm('Remove item?')"><i class="bi bi-trash"></i></button>
          </div>
        </form>
      <?php endforeach; ?>
      <form method="post" class="row g-2 align-items-center pt-3">
        <?= csrf_field() ?><input type="hidden" name="act" value="save_oos">
        <div class="col-3 col-md-2"><input name="time_label" class="form-control form-control-sm" placeholder="10:00"></div>
        <div class="col-9 col-md-5"><input name="item" class="form-control form-control-sm" placeholder="e.g. Hymn – Amazing Grace" required></div>
        <div class="col-8 col-md-3"><input name="person" class="form-control form-control-sm" placeholder="Led by"></div>
        <div class="col-4 col-md-2"><button class="btn btn-sm btn-ink w-100"><i class="bi bi-plus"></i> Add</button></div>
      </form>
    </div>
  </div>
  <div class="col-xl-5">
    <div class="card-soft p-4 bg-cream">
      <h3 class="h6"><i class="bi bi-lightbulb text-gold"></i> Tips for accurate pins</h3>
      <ul class="small mb-0">
        <li>Stand at the venue and tap <strong>Use my current location</strong>.</li>
        <li>Or paste a Google Maps link — we extract the coordinates.</li>
        <li>Or search the place name, then drag the pin to the exact gate.</li>
        <li>Add a coordinator's phone so guests can call if lost.</li>
      </ul>
    </div>
  </div>
</div>

<div class="modal fade" id="eventModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <form method="post" class="modal-content">
      <?= csrf_field() ?><input type="hidden" name="act" value="save_event"><input type="hidden" name="eid" id="ev_id">
      <div class="modal-header"><h5 class="modal-title" id="evTitle">Programme event</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-4">
          <div class="col-lg-5">
            <div class="row g-2">
              <div class="col-12"><label class="form-label">Title *</label><input name="title" id="ev_title" class="form-control" required list="evTitles"><datalist id="evTitles"><option>Body viewing</option><option>Cortege departs</option><option>Funeral service</option><option>Requiem mass</option><option>Burial</option><option>Reception & refreshments</option><option>Funeral committee meeting</option><option>Cremation</option></datalist></div>
              <div class="col-6"><label class="form-label">Date *</label><input type="date" name="event_date" id="ev_date" class="form-control" required></div>
              <div class="col-3"><label class="form-label">Start *</label><input type="time" name="start_time" id="ev_start" class="form-control" required></div>
              <div class="col-3"><label class="form-label">End</label><input type="time" name="end_time" id="ev_end" class="form-control"></div>
              <div class="col-12"><label class="form-label">Venue name</label><input name="venue" id="ev_venue" class="form-control" placeholder="e.g. PCEA Gatundu Parish"></div>
              <div class="col-12"><label class="form-label">Address / landmark</label><input name="address" id="ev_address" class="form-control" placeholder="e.g. Off Thika road, opposite the market"></div>
              <div class="col-12"><label class="form-label">Details</label><textarea name="description" id="ev_desc" class="form-control" rows="2"></textarea></div>
              <div class="col-6"><label class="form-label">Coordinator</label><input name="contact_name" id="ev_cname" class="form-control"></div>
              <div class="col-6"><label class="form-label">Coordinator phone</label><input name="contact_phone" id="ev_cphone" class="form-control"></div>
              <div class="col-6"><label class="form-label">Latitude</label><input name="lat" id="ev_lat" class="form-control" inputmode="decimal"></div>
              <div class="col-6"><label class="form-label">Longitude</label><input name="lng" id="ev_lng" class="form-control" inputmode="decimal"></div>
              <input type="hidden" name="sort" id="ev_sort" value="0">
            </div>
          </div>
          <div class="col-lg-7">
            <label class="form-label">Pin the exact location</label>
            <div class="input-group mb-2">
              <input id="placeSearch" class="form-control" placeholder="Search a place, or paste a Google Maps link">
              <button type="button" class="btn btn-outline-secondary" id="placeBtn"><i class="bi bi-search"></i></button>
            </div>
            <div id="searchResults" class="list-group mb-2 small"></div>
            <div id="pickMap" class="map-box" style="height:360px"></div>
            <div class="d-flex flex-wrap gap-2 mt-2">
              <button type="button" class="btn btn-sm btn-outline-primary" id="myLoc"><i class="bi bi-crosshair"></i> Use my current location</button>
              <button type="button" class="btn btn-sm btn-outline-secondary" id="clearPin"><i class="bi bi-x"></i> Clear pin</button>
              <span class="small-muted align-self-center">Tap the map or drag the pin to adjust.</span>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancel</button><button class="btn btn-gold rounded-pill px-4">Save event</button></div>
    </form>
  </div>
</div>
<?php
$extraFoot = <<<'HTML'
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script>
(function(){
  const modal = document.getElementById('eventModal');
  const $ = id => document.getElementById(id);
  let map, marker;
  const KENYA = [-1.286389, 36.817223];

  function setPin(lat, lng, zoom){
    lat = parseFloat(lat); lng = parseFloat(lng);
    if (isNaN(lat) || isNaN(lng)) return;
    $('ev_lat').value = lat.toFixed(7); $('ev_lng').value = lng.toFixed(7);
    if (!marker) { marker = L.marker([lat, lng], {draggable: true}).addTo(map); marker.on('dragend', e => { const p = e.target.getLatLng(); setPin(p.lat, p.lng); }); }
    else marker.setLatLng([lat, lng]);
    if (zoom) map.setView([lat, lng], zoom);
  }

  // Pulls coordinates out of pasted Google/Apple/Waze links or "lat,lng" text
  function parseCoords(text){
    const pats = [/@(-?\d+\.\d+),(-?\d+\.\d+)/, /!3d(-?\d+\.\d+)!4d(-?\d+\.\d+)/, /[?&](?:q|ll|daddr|destination|query)=(-?\d+\.\d+)(?:,|%2C)\s*(-?\d+\.\d+)/, /^\s*(-?\d+\.\d+)\s*,\s*(-?\d+\.\d+)\s*$/];
    for (const p of pats) { const m = text.match(p); if (m) return [m[1], m[2]]; }
    return null;
  }

  modal.addEventListener('show.bs.modal', function(e){
    const ev = JSON.parse(e.relatedTarget.dataset.event || '{}');
    $('evTitle').textContent = ev.id ? 'Edit event' : 'Add event';
    $('ev_id').value = ev.id || '';
    $('ev_title').value = ev.title || '';
    $('ev_date').value = ev.event_date || '';
    $('ev_start').value = (ev.start_time || '').slice(0,5);
    $('ev_end').value = (ev.end_time || '').slice(0,5);
    $('ev_venue').value = ev.venue || '';
    $('ev_address').value = ev.address || '';
    $('ev_desc').value = ev.description || '';
    $('ev_cname').value = ev.contact_name || '';
    $('ev_cphone').value = ev.contact_phone || '';
    $('ev_lat').value = ev.lat || '';
    $('ev_lng').value = ev.lng || '';
    $('ev_sort').value = ev.sort || 0;
    $('searchResults').innerHTML = ''; $('placeSearch').value = '';
  });

  modal.addEventListener('shown.bs.modal', function(){
    if (!map) {
      map = L.map('pickMap').setView(KENYA, 7);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19, attribution: '&copy; OpenStreetMap'}).addTo(map);
      map.on('click', e => setPin(e.latlng.lat, e.latlng.lng));
    }
    map.invalidateSize();
    if (marker) { map.removeLayer(marker); marker = null; }
    if ($('ev_lat').value && $('ev_lng').value) setPin($('ev_lat').value, $('ev_lng').value, 16);
    else map.setView(KENYA, 7);
  });

  ['ev_lat','ev_lng'].forEach(id => $(id).addEventListener('change', () => setPin($('ev_lat').value, $('ev_lng').value, 16)));

  async function search(){
    const q = $('placeSearch').value.trim();
    if (!q) return;
    const c = parseCoords(q);
    if (c) { setPin(c[0], c[1], 17); $('searchResults').innerHTML = ''; toast('Coordinates added from link'); return; }
    if (/goo\.gl|maps\.app/.test(q)) { toast('Short links cannot be read. Open the link, then copy the full URL from the address bar.'); return; }
    $('searchResults').innerHTML = '<div class="list-group-item">Searching…</div>';
    try {
      const r = await fetch('https://nominatim.openstreetmap.org/search?format=json&limit=5&countrycodes=ke&q=' + encodeURIComponent(q), {headers: {'Accept-Language': 'en'}});
      const list = await r.json();
      $('searchResults').innerHTML = '';
      if (!list.length) { $('searchResults').innerHTML = '<div class="list-group-item text-muted">No match. Try a nearby landmark, or tap the map.</div>'; return; }
      list.forEach(p => {
        const b = document.createElement('button'); b.type = 'button'; b.className = 'list-group-item list-group-item-action';
        b.textContent = p.display_name;
        b.onclick = () => { setPin(p.lat, p.lon, 17); if (!$('ev_address').value) $('ev_address').value = p.display_name.split(',').slice(0,3).join(','); $('searchResults').innerHTML = ''; };
        $('searchResults').appendChild(b);
      });
    } catch (e) { $('searchResults').innerHTML = '<div class="list-group-item text-danger">Search unavailable. Tap the map instead.</div>'; }
  }
  $('placeBtn').addEventListener('click', search);
  $('placeSearch').addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); search(); } });

  $('myLoc').addEventListener('click', function(){
    if (!navigator.geolocation) return toast('Location is not supported on this device');
    this.disabled = true;
    navigator.geolocation.getCurrentPosition(p => { this.disabled = false; setPin(p.coords.latitude, p.coords.longitude, 18); toast('Pinned to your location (±' + Math.round(p.coords.accuracy) + 'm)'); },
      () => { this.disabled = false; toast('Could not get your location. Allow location access and try again.'); }, {enableHighAccuracy: true, timeout: 15000});
  });
  $('clearPin').addEventListener('click', () => { $('ev_lat').value = ''; $('ev_lng').value = ''; if (marker) { map.removeLayer(marker); marker = null; } });
})();
</script>
HTML;
