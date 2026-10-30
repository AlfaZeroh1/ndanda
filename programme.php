<?php
/** Public interactive funeral programme with live status, map and directions (/p/{slug}). */
require __DIR__ . '/includes/bootstrap.php';
$f = row('SELECT * FROM funerals WHERE slug = ?', [input('s')]);
$owner = $f && can_manage($f);
if (!$f || ($f['status'] !== 'active' && !$owner) || !has_service($f, 'programme')) {
    http_response_code(404);
    exit('This programme is not available.');
}
$events = rows('SELECT * FROM programme_events WHERE funeral_id = ? ORDER BY event_date, start_time, sort', [$f['id']]);
$oos = rows('SELECT * FROM order_of_service WHERE funeral_id = ? ORDER BY sort, id', [$f['id']]);
$byDay = [];
foreach ($events as $ev) $byDay[$ev['event_date']][] = $ev;

$pageTitle = 'Funeral programme – ' . $f['deceased_name'];
$metaDesc = 'Programme, venues and directions for the funeral of ' . $f['deceased_name'];
$ogImage = $f['photo'] ? img($f['photo']) : null;
$hideNav = $hideFooter = $hideTestBanner = true;
$bodyClass = 'memorial';
$bodyAttr = 'style="' . e(brand_style($f)) . '"';
$extraHead = '<link href="' . e(brand_font_href($f)) . '" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">';
require __DIR__ . '/includes/header.php';
$mapPoints = [];
foreach ($events as $i => $ev) {
    if ($ev['lat'] !== null && $ev['lng'] !== null) {
        $mapPoints[] = ['n' => $i + 1, 'lat' => (float) $ev['lat'], 'lng' => (float) $ev['lng'], 'title' => $ev['title'], 'venue' => $ev['venue'], 'time' => date('H:i', strtotime($ev['start_time']))];
    }
}
?>
<header class="m-hero" style="min-height:230px;<?= $f['cover'] ? 'background-image:url(' . e(img($f['cover'])) . ')' : '' ?>">
  <div class="container m-brandbar">
    <a href="<?= e(tribute_url($f)) ?>" class="text-white text-decoration-none small"><i class="bi bi-arrow-left"></i> Tribute page</a>
    <div class="d-flex gap-2">
      <button class="btn btn-light btn-sm rounded-pill" data-share="<?= e(programme_url($f)) ?>" data-title="<?= e($pageTitle) ?>"><i class="bi bi-share"></i> Share</button>
    </div>
  </div>
  <div class="container position-relative text-center text-white pb-5" style="z-index:2">
    <?php if ($f['brand_logo']): ?><img src="<?= e(img($f['brand_logo'])) ?>" alt="" style="max-height:40px" class="mb-2"><?php endif; ?>
    <div class="text-uppercase small" style="letter-spacing:.25em;color:var(--brand-accent)">Funeral programme</div>
    <h1 class="text-white m-font mb-0" style="font-size:clamp(1.8rem,4vw,2.8rem)"><?= e($f['deceased_name']) ?></h1>
    <div class="opacity-75"><?= e(life_years($f)) ?></div>
  </div>
</header>

<div class="container py-4" style="max-width:920px">
  <div id="nowBanner" class="alert d-none rounded-4 shadow-sm" style="background:#fff;border-left:5px solid var(--brand-accent)"></div>

  <?php if (!$events): ?><div class="empty"><i class="bi bi-calendar2"></i>The programme will be published soon.</div><?php endif; ?>

  <?php if ($mapPoints): ?>
  <div class="d-flex justify-content-between align-items-center mb-2 mt-2">
    <h2 class="h5 m-font mb-0" style="color:var(--brand)"><i class="bi bi-map"></i> All venues</h2>
    <button class="btn btn-sm btn-outline-brand rounded-pill" id="locateBtn"><i class="bi bi-crosshair"></i> Show my location</button>
  </div>
  <div id="progMap" class="mb-4"></div>
  <?php endif; ?>

  <?php $n = 0; foreach ($byDay as $day => $list): ?>
    <div class="day-label"><?= e(fmt_date($day, 'l, j F Y')) ?></div>
    <div class="timeline">
      <?php foreach ($list as $ev): $n++;
        $start = $ev['event_date'] . 'T' . $ev['start_time'] . '+03:00';
        $end = $ev['event_date'] . 'T' . ($ev['end_time'] ?: date('H:i:s', strtotime($ev['start_time']) + 3600)) . '+03:00';
        $ll = $ev['lat'] !== null ? $ev['lat'] . ',' . $ev['lng'] : null; ?>
      <div class="tl-item" data-start="<?= e($start) ?>" data-end="<?= e($end) ?>" data-title="<?= e($ev['title']) ?>" data-venue="<?= e($ev['venue']) ?>">
        <span class="tl-dot"></span>
        <div class="tl-card">
          <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
            <span class="tl-time"><span class="badge rounded-pill me-1" style="background:var(--brand)"><?= $n ?></span><?= e(date('g:i A', strtotime($ev['start_time']))) ?><?= $ev['end_time'] ? ' – ' . e(date('g:i A', strtotime($ev['end_time']))) : '' ?></span>
            <span><span class="tl-status now">Happening now</span><span class="tl-status next">Up next</span><span class="tl-status done">Done</span></span>
          </div>
          <h3 class="h5 mt-2 mb-1"><?= e($ev['title']) ?></h3>
          <?php if ($ev['venue'] || $ev['address']): ?><div class="text-muted"><i class="bi bi-geo-alt-fill" style="color:var(--brand-accent)"></i> <strong><?= e($ev['venue']) ?></strong><?= $ev['address'] ? ' · ' . e($ev['address']) : '' ?></div><?php endif; ?>
          <?php if ($ev['description']): ?><p class="mt-2 mb-0 small"><?= nl2br(e($ev['description'])) ?></p><?php endif; ?>
          <div class="nav-btns d-flex flex-wrap gap-2 mt-3">
            <?php if ($ll): ?>
              <a class="btn btn-sm btn-brand" target="_blank" rel="noopener" href="https://www.google.com/maps/dir/?api=1&destination=<?= e($ll) ?>"><i class="bi bi-google"></i> Google Maps</a>
              <a class="btn btn-sm btn-outline-brand" target="_blank" rel="noopener" href="https://maps.apple.com/?daddr=<?= e($ll) ?>"><i class="bi bi-apple"></i> Apple Maps</a>
              <a class="btn btn-sm btn-outline-brand" target="_blank" rel="noopener" href="https://waze.com/ul?ll=<?= e($ll) ?>&navigate=yes"><i class="bi bi-sign-turn-right"></i> Waze</a>
              <a class="btn btn-sm btn-outline-secondary d-md-none" href="geo:<?= e($ll) ?>?q=<?= e($ll) ?>(<?= rawurlencode($ev['venue'] ?: $ev['title']) ?>)"><i class="bi bi-phone"></i> Other app</a>
            <?php elseif ($ev['venue'] || $ev['address']): ?>
              <a class="btn btn-sm btn-brand" target="_blank" rel="noopener" href="https://www.google.com/maps/search/?api=1&query=<?= rawurlencode(trim($ev['venue'] . ' ' . $ev['address'])) ?>"><i class="bi bi-search"></i> Find on map</a>
            <?php endif; ?>
            <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('api/ics.php?id=' . $ev['id'])) ?>"><i class="bi bi-calendar-plus"></i> Add to calendar</a>
            <?php if ($ev['contact_phone']): ?><a class="btn btn-sm btn-outline-secondary" href="tel:<?= e(preg_replace('/[^\d+]/', '', $ev['contact_phone'])) ?>"><i class="bi bi-telephone"></i> <?= e($ev['contact_name'] ?: 'Call coordinator') ?></a><?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>

  <?php if ($oos): ?>
  <div class="m-card mt-5">
    <h2 class="h4 m-font" style="color:var(--brand)">Order of service</h2>
    <?php foreach ($oos as $o): ?>
      <div class="oos-row"><div class="oos-time"><?= e($o['time_label']) ?></div><div><div class="fw-semibold"><?= e($o['item']) ?></div><?php if ($o['person']): ?><div class="small text-muted"><?= e($o['person']) ?></div><?php endif; ?><?php if ($o['details']): ?><div class="small mt-1"><?= nl2br(e($o['details'])) ?></div><?php endif; ?></div></div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if (has_service($f, 'livestream') && $f['livestream_url']): ?>
  <a href="<?= e(tribute_url($f)) ?>#live" class="btn btn-danger rounded-pill w-100 mt-4 py-3"><i class="bi bi-broadcast"></i> Can't attend? Watch the live stream</a>
  <?php endif; ?>

  <div class="text-center mt-5 small text-muted">
    <?php if ($f['brand_name']): ?>Organised by <strong><?= e($f['brand_name']) ?></strong> · <?php endif; ?>Powered by <a href="<?= e(url()) ?>" style="color:var(--brand)"><?= e(setting('site_name')) ?></a>
  </div>
</div>
<?php
$pts = json_encode($mapPoints);
$extraFoot = '<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script>
(function(){
  // Live status: mark each event as done / now / next
  function refresh(){
    const now = Date.now(); let nextSet = false, nowItem = null, nextItem = null;
    document.querySelectorAll(".tl-item").forEach(function(el){
      const s = Date.parse(el.dataset.start), e = Date.parse(el.dataset.end);
      el.classList.remove("is-now","is-next","is-done");
      if (now >= e) el.classList.add("is-done");
      else if (now >= s) { el.classList.add("is-now"); nowItem = nowItem || el; }
      else if (!nextSet) { el.classList.add("is-next"); nextSet = true; nextItem = el; }
    });
    const b = document.getElementById("nowBanner");
    const pick = nowItem || nextItem;
    if (pick) {
      const label = nowItem ? "<span class=\"live-pill me-2\">NOW</span>" : "<strong style=\"color:var(--brand-accent)\">UP NEXT</strong> · ";
      const when = nowItem ? "" : new Date(Date.parse(pick.dataset.start)).toLocaleString([], {weekday:"short", hour:"numeric", minute:"2-digit"}) + " · ";
      b.innerHTML = label + when + "<strong></strong> <span class=\"text-muted\"></span>";
      b.querySelector("strong:last-of-type").textContent = pick.dataset.title;
      b.querySelector(".text-muted").textContent = pick.dataset.venue ? "at " + pick.dataset.venue : "";
      b.classList.remove("d-none");
    } else b.classList.add("d-none");
  }
  refresh(); setInterval(refresh, 30000);

  const pts = ' . $pts . ';
  if (!pts.length || typeof L === "undefined") return;
  const brand = getComputedStyle(document.body).getPropertyValue("--brand").trim() || "#1f2937";
  const map = L.map("progMap", {scrollWheelZoom: false});
  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {maxZoom: 19, attribution: "&copy; OpenStreetMap"}).addTo(map);
  const latlngs = [];
  pts.forEach(function(p){
    const icon = L.divIcon({className: "", html: "<div style=\"background:" + brand + ";color:#fff;width:30px;height:30px;border-radius:50%;display:grid;place-items:center;font-weight:700;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.3)\">" + p.n + "</div>", iconSize: [30,30], iconAnchor: [15,15]});
    const m = L.marker([p.lat, p.lng], {icon: icon}).addTo(map);
    const div = document.createElement("div");
    const t = document.createElement("strong"); t.textContent = p.time + " · " + p.title;
    const v = document.createElement("div"); v.textContent = p.venue || "";
    const a = document.createElement("a"); a.href = "https://www.google.com/maps/dir/?api=1&destination=" + p.lat + "," + p.lng; a.target = "_blank"; a.textContent = "Directions →";
    div.append(t, v, a); m.bindPopup(div);
    latlngs.push([p.lat, p.lng]);
  });
  if (latlngs.length > 1) { L.polyline(latlngs, {color: brand, dashArray: "6 8", weight: 3}).addTo(map); map.fitBounds(latlngs, {padding: [40, 40]}); }
  else map.setView(latlngs[0], 14);

  const btn = document.getElementById("locateBtn");
  if (btn) btn.addEventListener("click", function(){
    if (!navigator.geolocation) return toast("Location not supported on this device");
    navigator.geolocation.getCurrentPosition(function(pos){
      const me = [pos.coords.latitude, pos.coords.longitude];
      L.circleMarker(me, {radius: 9, color: "#0d6efd", fillColor: "#0d6efd", fillOpacity: .8}).addTo(map).bindPopup("You are here").openPopup();
      map.fitBounds(latlngs.concat([me]), {padding: [40, 40]});
    }, function(){ toast("Could not get your location"); });
  });
})();
</script>';
require __DIR__ . '/includes/footer.php';
