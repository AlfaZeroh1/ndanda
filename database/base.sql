-- Base data needed in production (settings, services, CMS pages). Not test data.
SET NAMES utf8mb4;

INSERT IGNORE INTO settings (skey, svalue) VALUES
('site_name','Ndanda Tributes'),
('site_tagline','Honouring lives, beautifully and digitally'),
('company_name','Ndanda Events Ltd'),
('company_phone','+254 792 487 000'),
('company_email','info@ndandaevents.com'),
('company_address','Nairobi, Kenya'),
('company_whatsapp','254792487000'),
('social_instagram','https://instagram.com/ndanda_events_ltd'),
('social_youtube','https://youtube.com/@ndandaeventsltd911'),
('social_facebook',''),
('social_tiktok',''),
('company_website','https://www.ndandaevents.com'),
('hero_title','Celebrate a life. Gather everyone. Go paperless.'),
('hero_subtitle','Create a beautiful online tribute, an interactive funeral programme with live directions, QR codes, printable memorials and QR plaques — all in one place, branded your way.'),
('currency','KES'),
('test_mode','1'),
('allow_simulation','1'),
('pretty_urls','1'),
('mpesa_env','sandbox'),
('mpesa_type','paybill'),
('mpesa_shortcode','174379'),
('mpesa_c2b_shortcode','600000'),
('mpesa_passkey','bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919'),
('mpesa_consumer_key',''),
('mpesa_consumer_secret',''),
('mpesa_callback_base',''),
('mpesa_callback_secret',''),
('mpesa_token_cache',''),
('mail_from','no-reply@ndandaevents.com'),
('admin_notify_email','info@ndandaevents.com');

INSERT IGNORE INTO services (code, name, description, icon, price, is_standard, is_active, sort) VALUES
('tribute_page','Online Tribute Page','A beautiful memorial website with life story, obituary, photo gallery, candles and condolence messages — plus a shareable form link so family and friends can add their own tributes.','bi-flower1',5000,0,1,1),
('programme','Interactive Funeral Programme','Every venue and time in one live programme: GPS pins that open in Google Maps, Apple Maps or Waze, order of service, “happening now” updates, add-to-calendar and a print-ready booklet.','bi-signpost-split',5000,0,1,2),
('qr_code','QR Codes','Scannable QR codes for the tribute page, programme and tribute form — ready for invitations, banners, newspapers and programmes.','bi-qr-code',5000,0,1,3),
('memorial_print','Printable Memorial','Print-ready memorial cards and posters designed with your photos, colours and logo.','bi-printer',5000,0,1,4),
('qr_plaque','QR Memorial Plaque','A print-ready plaque design for the graveside, home or church that links visitors to the life story forever.','bi-award',5000,0,1,5),
('livestream','Live Streaming','Ndanda Events streams the service so loved ones far away can be present. The stream is embedded on the tribute page.','bi-broadcast',5000,0,1,6),
('photography','Photography & Videography','Professional, respectful coverage of the funeral by the Ndanda Events crew, delivered to the online gallery.','bi-camera',5000,0,1,7),
('preparations','Funeral Preparations Workspace','A private central place for documents, notes, tasks, committee assignments and the funeral budget. Included as standard.','bi-folder2-open',0,1,1,8);

INSERT IGNORE INTO pages (slug, title, subtitle, content, meta_description, is_system, is_published, show_in_footer) VALUES
('about','About Us','Moments made unforgettable — including the ones that matter most.','<p><strong>Ndanda Events</strong> is a Nairobi-based event design, production and media studio. From intimate gatherings to large-scale productions we handle concept, design, logistics, coverage and delivery — weddings, corporate events, launches, staging and celebrations of life.</p><p><strong>Ndanda Tributes</strong> is our digital platform built for Kenyan families. Planning a funeral is hard, and it is still largely done on paper: printed programmes, phone calls for directions, contribution lists in exercise books. We built Ndanda Tributes so that a family can honour their loved one with dignity while keeping everyone informed in one beautiful, shareable place.</p><h3>What we believe</h3><ul><li><strong>Every life deserves a beautiful story.</strong> Tribute pages that can be revisited for years.</li><li><strong>Nobody should get lost on the way to say goodbye.</strong> Live programmes with GPS directions to every venue.</li><li><strong>Your moment, your look.</strong> Families and funeral planners brand each page with their own colours and logos.</li></ul><p>Any funeral planner, church, funeral home or family can use the platform — it is a service open to all.</p>','About Ndanda Events and the Ndanda Tributes platform.',1,1,0),
('how-it-works','How It Works','From package to a fully digital funeral in four simple steps.','<p>Ndanda Tributes replaces paper-heavy funeral planning with one seamless online space for the family, the committee and every mourner.</p>','How Ndanda Tributes works.',1,1,0),
('livestream','Live Streaming','Let loved ones near and far be present.','<p>Not everyone can travel to the funeral. Our crew provides multi-camera live streaming of the service, church and burial, embedded straight into the tribute page so relatives in the diaspora can follow, pray and share their condolences in real time.</p><ul><li>HD multi-camera production</li><li>Stream embedded on the tribute page and shared via QR</li><li>Recording delivered to the family afterwards</li></ul>','Funeral live streaming by Ndanda Events.',1,1,0),
('photography','Photography & Videography','Respectful, professional coverage of a final farewell.','<p>The Ndanda Events media team documents the funeral with sensitivity and care — from the viewing and church service to the burial and family moments. Photos are delivered directly to the online tribute gallery for everyone to cherish.</p><ul><li>Discreet, experienced photographers</li><li>Highlights video and full-service recording</li><li>Drone coverage on request</li></ul>','Funeral photography and videography by Ndanda Events.',1,1,0),
('faq','Frequently Asked Questions','','<h4>Who can use Ndanda Tributes?</h4><p>Anyone — families, funeral planners, churches and funeral homes. Each funeral account can be branded with its own colours and logo.</p><h4>How do I pay?</h4><p>Through M-Pesa. At checkout you receive an STK push on your phone; simply enter your PIN. You can also pay via our Paybill using your order number as the account number.</p><h4>How long does the tribute page stay online?</h4><p>Tribute pages remain online as a lasting memorial.</p><h4>Can family members add tributes?</h4><p>Yes. Every funeral account has a shareable tribute form link. You decide whether tributes are published instantly or after your approval.</p><h4>Is the funeral preparations workspace private?</h4><p>Yes. Documents, notes, tasks and the budget are only visible to the account owner.</p>','Frequently asked questions.',1,1,1),
('terms','Terms of Service','','<p>By using Ndanda Tributes you agree to provide accurate information, to only publish content you have the right to share, and to treat others with respect. Ndanda Events may remove content that is offensive or unlawful. Payments for services are non-refundable once the service has been delivered.</p>','Terms of service.',1,1,1),
('privacy','Privacy Policy','','<p>We collect only the information needed to provide our services: account details, funeral content you create, and payment records. Private preparation documents are never shown publicly. We do not sell your data. Contact us to request deletion of your data.</p>','Privacy policy.',1,1,1);
