-- SAMPLE / TEST DATA. Every top-level row is flagged is_test = 1 and is removed by Admin → Go Live.
-- The test planner user is inserted by install.php (it needs a PHP password hash), as email planner@test.com
SET NAMES utf8mb4;
SET @tu = (SELECT id FROM users WHERE email = 'planner@test.com' LIMIT 1);

INSERT INTO funerals (user_id, slug, form_token, deceased_name, nickname, heading, birth_date, death_date, birth_place, resting_place, photo, quote, obituary, bio, family, contribution_details, status, visibility, brand_primary, brand_accent, brand_font, brand_name, brand_tagline, candles, views, is_featured, is_test, livestream_url, livestream_at) VALUES
(@tu, 'joseph-kamau-njoroge', 'a1b2c3d4e5f6a7b8c9d0', 'Joseph Kamau Njoroge', 'Mzee Kamau', 'Celebrating the life of', '1942-03-14', '2026-10-21', 'Gatundu, Kiambu', 'Family home, Gatundu', 'https://randomuser.me/api/portraits/men/86.jpg', 'I have fought the good fight, I have finished the race, I have kept the faith. — 2 Timothy 4:7', 'It is with humble acceptance of God''s will that we announce the passing of Mzee Joseph Kamau Njoroge, retired headteacher of Gatundu Primary School, which occurred peacefully at his home on 21st October 2026.', 'Mzee Joseph Kamau Njoroge was born in 1942 in Gatundu, the second of seven children. A gifted student, he trained as a teacher at Kagumo Teachers College and served Kenyan children for 38 years, the last 15 as headteacher of Gatundu Primary School.\n\nHe married the love of his life, Mary Wambui, in 1968. Together they raised six children and were blessed with fourteen grandchildren. He was a pillar of PCEA Gatundu, a respected elder, and a passionate farmer whose tea bushes were the envy of the village.\n\nThose who knew him remember his laughter, his firm but loving discipline, and his belief that education opens every door. Rest well, Mwalimu.', 'Husband to Mary Wambui. Father of Peter, Jane, David, Esther, Samuel and Ruth. Grandfather of 14. Brother to the late Njoroge, Wanjiku, Kariuki, Nduta, Mburu and Gathoni.', 'Funeral committee contributions: M-Pesa Paybill 222111, Account: KAMAU. Treasurer: Peter Kamau 0712 000 111.', 'active', 'public', '#1f3a2e', '#c9a227', 'Playfair Display', 'Ndanda Events', 'Moments made unforgettable', 128, 1420, 1, 1, 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', DATE_ADD(CURDATE(), INTERVAL 3 DAY) + INTERVAL 10 HOUR);
SET @f1 = LAST_INSERT_ID();

INSERT INTO funerals (user_id, slug, form_token, deceased_name, nickname, heading, birth_date, death_date, birth_place, resting_place, photo, quote, obituary, bio, family, status, visibility, brand_primary, brand_accent, brand_font, candles, views, is_featured, is_test) VALUES
(@tu, 'grace-wanjiru-mwangi', 'b2c3d4e5f6a7b8c9d0e1', 'Grace Wanjiru Mwangi', 'Mama Grace', 'In loving memory of', '1950-07-02', '2026-10-15', 'Othaya, Nyeri', 'Othaya, Nyeri', 'https://randomuser.me/api/portraits/women/79.jpg', 'A mother''s love is forever.', 'The family of Grace Wanjiru Mwangi announces with deep sorrow her promotion to glory on 15th October 2026 after a short illness.', 'Mama Grace was a nurse for 30 years at Nyeri Provincial General Hospital, a woman of prayer and the heart of her family. She founded the Othaya Women''s Chama that has educated over 40 children.', 'Wife of the late Mwangi Gichuki. Mother of Kevin, Lucy and Anne.', 'active', 'public', '#4a2c4f', '#d4a5a5', 'Cormorant Garamond', 86, 902, 1, 1),
(@tu, 'peter-otieno-odhiambo', 'c3d4e5f6a7b8c9d0e1f2', 'Dr. Peter Otieno Odhiambo', 'Daktari', 'Celebrating the life of', '1968-11-23', '2026-10-09', 'Bondo, Siaya', 'Bondo, Siaya', 'https://randomuser.me/api/portraits/men/52.jpg', 'He healed many and loved all.', 'It is with great sadness that we announce the passing of Dr. Peter Otieno Odhiambo, consultant physician at Jaramogi Oginga Odinga Teaching & Referral Hospital.', 'Dr. Otieno dedicated his life to medicine and mentoring young doctors across the Lake region. A keen footballer and a devoted father, he leaves a legacy of compassion.', 'Husband to Dr. Lilian Achieng. Father to Brian, Michelle and Ian.', 'active', 'public', '#0f2c4d', '#7fb3d5', 'Lora', 64, 755, 1, 1),
(@tu, 'faith-chebet-kiprono', 'd4e5f6a7b8c9d0e1f2a3', 'Faith Chebet Kiprono', 'Chebet', 'Forever in our hearts', '1985-01-30', '2026-10-02', 'Eldoret, Uasin Gishu', 'Kapseret, Uasin Gishu', 'https://randomuser.me/api/portraits/women/44.jpg', 'Run your race with joy.', 'We regret to announce the sudden passing of Faith Chebet Kiprono, athlete, teacher and friend.', 'Chebet was a celebrated marathoner turned PE teacher who coached hundreds of young runners in Eldoret. Her smile lit up every track.', 'Daughter of Mr. and Mrs. Kiprono. Mother of Tanui and Jepkemboi.', 'active', 'public', '#7a2e1d', '#f0b429', 'Montserrat', 40, 510, 0, 1),
(@tu, 'samuel-mutua-musyoka', 'e5f6a7b8c9d0e1f2a3b4', 'Hon. Samuel Mutua Musyoka', 'Mzee Mutua', 'Celebrating the life of', '1955-05-18', '2026-09-28', 'Kangundo, Machakos', 'Kangundo, Machakos', 'https://randomuser.me/api/portraits/men/71.jpg', 'Service to others is the rent we pay for our room here on earth.', 'The family announces the passing of Hon. Samuel Mutua Musyoka, former Member of County Assembly and community leader.', 'Mzee Mutua served his community for over three decades, championing water projects and bursaries across Kangundo.', 'Husband of Rose Mwikali. Father of five.', 'active', 'public', '#2b2b2b', '#b8913a', 'Libre Baskerville', 52, 640, 0, 1),
(@tu, 'agnes-nafula-wekesa', 'f6a7b8c9d0e1f2a3b4c5', 'Agnes Nafula Wekesa', 'Mama Nafula', 'In loving memory of', '1972-08-08', '2026-09-20', 'Bungoma', 'Kimilili, Bungoma', 'https://randomuser.me/api/portraits/women/65.jpg', 'Her love was her legacy.', 'With sorrow we announce the passing of Agnes Nafula Wekesa, businesswoman and choir leader.', 'Agnes ran a thriving tailoring business in Kimilili and led the St. Mary''s choir for 20 years.', 'Wife of Stephen Wekesa. Mother of four.', 'active', 'public', '#3d2a5c', '#e2b8ff', 'EB Garamond', 33, 410, 0, 1);
SET @f2 = (SELECT id FROM funerals WHERE slug='grace-wanjiru-mwangi');
SET @f3 = (SELECT id FROM funerals WHERE slug='peter-otieno-odhiambo');
SET @f4 = (SELECT id FROM funerals WHERE slug='faith-chebet-kiprono');
SET @f5 = (SELECT id FROM funerals WHERE slug='samuel-mutua-musyoka');
SET @f6 = (SELECT id FROM funerals WHERE slug='agnes-nafula-wekesa');

-- Services owned by each sample funeral
INSERT IGNORE INTO funeral_services (funeral_id, service_id) SELECT @f1, id FROM services WHERE code IN ('tribute_page','programme','qr_code','memorial_print','qr_plaque','livestream','preparations');
INSERT IGNORE INTO funeral_services (funeral_id, service_id) SELECT f.id, s.id FROM funerals f JOIN services s ON s.code IN ('tribute_page','preparations') WHERE f.id IN (@f2,@f3,@f4,@f5,@f6);
INSERT IGNORE INTO funeral_services (funeral_id, service_id) SELECT @f3, id FROM services WHERE code IN ('programme','qr_code');

-- Tributes (10)
INSERT INTO tributes (funeral_id, author_name, relationship, location, message, status, is_test) VALUES
(@f1, 'Esther Kamau', 'Daughter', 'Nairobi', 'Baba, you taught us that kindness is strength. Every lesson you gave lives on in your grandchildren. We will miss your morning walks to the tea farm and your stories by the fire.', 'approved', 1),
(@f1, 'James Mwangi', 'Former student', 'Thika', 'Mwalimu Kamau believed in me when nobody else did. I am an engineer today because of him. Rest in peace, sir.', 'approved', 1),
(@f1, 'PCEA Gatundu Men''s Fellowship', 'Church', 'Gatundu', 'A faithful elder and a true servant of God. We celebrate a life well lived.', 'approved', 1),
(@f1, 'Grace Njeri', 'Neighbour', 'Gatundu', 'Mzee always had a word of encouragement for every child in the village. Pole sana to the family.', 'pending', 1),
(@f2, 'Lucy Mwangi', 'Daughter', 'London, UK', 'Mum, your prayers carried us. Thank you for every sacrifice. Lala salama.', 'approved', 1),
(@f2, 'Othaya Women''s Chama', 'Friends', 'Othaya', 'You founded us, you led us, you loved us. Your legacy lives in every child we educate.', 'approved', 1),
(@f3, 'Dr. Wafula Chebii', 'Colleague', 'Kisumu', 'A brilliant physician and a generous mentor. The Lake region has lost a giant.', 'approved', 1),
(@f4, 'Eldoret Running Club', 'Club', 'Eldoret', 'Chebet, you ran with joy and taught us to do the same. Forever our champion.', 'approved', 1),
(@f5, 'Kangundo Residents', 'Community', 'Kangundo', 'Thank you for the water projects that changed our lives. Rest in peace, Mheshimiwa.', 'approved', 1),
(@f6, 'St. Mary''s Choir', 'Choir', 'Kimilili', 'Your voice will forever echo in our hymns. Rest in eternal peace, Mama Nafula.', 'approved', 1);

-- Programme for the featured sample (Kiambu / Gatundu coordinates)
INSERT INTO programme_events (funeral_id, event_date, start_time, end_time, title, description, venue, address, lat, lng, contact_name, contact_phone, sort) VALUES
(@f1, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '17:00:00', '20:00:00', 'Funeral committee meeting & prayers', 'Final meeting and evening prayers with the family.', 'Kamau family home', 'Gatundu South, Kiambu', -1.0137000, 36.9081000, 'Peter Kamau', '0712000111', 1),
(@f1, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '07:30:00', '08:30:00', 'Body viewing & cortege departs', 'Viewing at the funeral home, cortege departs for the church.', 'Kiambu Level 5 Hospital Mortuary', 'Kiambu Town', -1.1710000, 36.8350000, 'David Kamau', '0722000222', 2),
(@f1, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '10:00:00', '13:00:00', 'Funeral service', 'Church service led by Rev. John Mbugua. Live streamed.', 'PCEA Gatundu Parish', 'Gatundu Town', -1.0182000, 36.9105000, 'Rev. John Mbugua', '0733000333', 3),
(@f1, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '14:00:00', '16:00:00', 'Burial & reception', 'Interment at the family home followed by refreshments.', 'Kamau family home', 'Gatundu South, Kiambu', -1.0137000, 36.9081000, 'Peter Kamau', '0712000111', 4);

INSERT INTO order_of_service (funeral_id, time_label, item, person, sort) VALUES
(@f1, '10:00', 'Processional hymn – “Nearer, My God, to Thee”', 'Church choir', 1),
(@f1, '10:10', 'Opening prayer', 'Elder Wainaina', 2),
(@f1, '10:20', 'Scripture reading – Psalm 23', 'Esther Kamau', 3),
(@f1, '10:35', 'Eulogy', 'Peter Kamau', 4),
(@f1, '11:15', 'Tributes from family & friends', 'MC – Samuel Kamau', 5),
(@f1, '12:00', 'Sermon', 'Rev. John Mbugua', 6);

-- Preparations workspace samples
INSERT INTO prep_tasks (funeral_id, title, assignee, due_date, is_done) VALUES
(@f1, 'Obtain burial permit', 'Peter Kamau', DATE_ADD(CURDATE(), INTERVAL 1 DAY), 1),
(@f1, 'Book tents and chairs (500 guests)', 'David Kamau', DATE_ADD(CURDATE(), INTERVAL 1 DAY), 0),
(@f1, 'Confirm caterer and menu', 'Ruth Kamau', DATE_ADD(CURDATE(), INTERVAL 2 DAY), 0),
(@f1, 'Share programme link on family WhatsApp groups', 'Esther Kamau', CURDATE(), 0);
INSERT INTO prep_budget (funeral_id, category, item, estimated, actual, is_paid) VALUES
(@f1, 'Mortuary', 'Mortuary fees', 45000, 42000, 1),
(@f1, 'Casket', 'Casket & hearse', 120000, 0, 0),
(@f1, 'Catering', 'Lunch for 500 guests', 150000, 0, 0),
(@f1, 'Venue', 'Tents, chairs & PA', 60000, 55000, 1),
(@f1, 'Media', 'Ndanda Tributes package', 35000, 35000, 1);
INSERT INTO prep_notes (funeral_id, title, body) VALUES
(@f1, 'Committee members', 'Chair: Samuel Kamau\nTreasurer: Peter Kamau\nSecretary: Esther Kamau'),
(@f1, 'Church requirements', 'Bring the burial permit and the printed order of service to the parish office by Friday.');
INSERT INTO contributions (funeral_id, name, phone, amount, status, source) VALUES
(@f1, 'Gatundu Teachers SACCO', '0700000001', 50000, 'received', 'planner'),
(@f1, 'John Kariuki', '0700000002', 10000, 'pledged', 'public'),
(@f1, 'Wanjiku family', '0700000003', 20000, 'received', 'planner');

-- Orders & M-Pesa transactions
INSERT INTO orders (reference, user_id, funeral_id, amount, status, phone, mpesa_receipt, paid_at, is_test, created_at) VALUES
('NDTEST01', @tu, @f1, 30000, 'paid', '254712000111', 'TJK1ABC2DE', NOW() - INTERVAL 8 DAY, 1, NOW() - INTERVAL 8 DAY),
('NDTEST02', @tu, @f3, 15000, 'paid', '254722000222', 'TJK3FGH4IJ', NOW() - INTERVAL 20 DAY, 1, NOW() - INTERVAL 20 DAY),
('NDTEST03', @tu, @f2, 5000, 'pending', '254733000333', NULL, NULL, 1, NOW() - INTERVAL 1 DAY);
INSERT INTO order_items (order_id, service_id, name, price) SELECT o.id, s.id, s.name, s.price FROM orders o JOIN services s ON s.code IN ('tribute_page','programme','qr_code','memorial_print','qr_plaque','livestream') WHERE o.reference='NDTEST01';
INSERT INTO order_items (order_id, service_id, name, price) SELECT o.id, s.id, s.name, s.price FROM orders o JOIN services s ON s.code IN ('tribute_page','programme','qr_code') WHERE o.reference='NDTEST02';
INSERT INTO order_items (order_id, service_id, name, price) SELECT o.id, s.id, s.name, s.price FROM orders o JOIN services s ON s.code IN ('memorial_print') WHERE o.reference='NDTEST03';
INSERT INTO mpesa_transactions (order_id, type, merchant_request_id, checkout_request_id, phone, amount, account_ref, result_code, result_desc, mpesa_receipt, status, is_test, created_at)
SELECT id, 'stk', CONCAT('TEST-MR-', id), CONCAT('ws_CO_TEST_', id), phone, amount, reference, IF(status='paid',0,1032), IF(status='paid','The service request is processed successfully.','Request cancelled by user'), mpesa_receipt, IF(status='paid','success','cancelled'), 1, created_at FROM orders WHERE reference IN ('NDTEST01','NDTEST02','NDTEST03');

-- Blog posts (3)
INSERT INTO posts (slug, title, excerpt, content, author, status, is_test, published_at) VALUES
('going-paperless-kenyan-funerals', 'Going paperless: how Kenyan families are digitising funerals', 'From WhatsApp programmes to QR plaques – the modern way to inform and honour.', '<p>Kenyan funerals bring together hundreds of relatives, friends and colleagues. Traditionally, programmes are printed in their thousands and directions shared by phone. A digital programme with GPS pins means nobody gets lost and every update reaches everyone instantly.</p><p>Families are also creating online tribute pages that stay up for years, allowing grandchildren to read the life story long after the burial.</p>', 'Ndanda Events', 'published', 1, NOW() - INTERVAL 5 DAY),
('writing-a-eulogy', 'How to write a heartfelt eulogy', 'Practical tips to tell the story of a life with love and honesty.', '<p>Start with the basics – birth, family, education and career – then share the stories that capture who they were. Ask relatives for memories, keep it to 10–15 minutes when read aloud, and end with hope.</p>', 'Ndanda Events', 'published', 1, NOW() - INTERVAL 12 DAY),
('funeral-budget-checklist', 'A funeral budget checklist for families', 'Mortuary, casket, catering, transport and more – plan without surprises.', '<p>Use the free Funeral Preparations Workspace to list every expense, assign committee members and track contributions. Common items include mortuary fees, casket, hearse, tents, catering, printing and media coverage.</p>', 'Ndanda Events', 'published', 1, NOW() - INTERVAL 20 DAY);

-- Inbox (2)
INSERT INTO messages (type, name, email, phone, subject, message, is_test) VALUES
('contact', 'Mary Achieng', 'mary@example.com', '0711000444', 'Package enquiry', 'Hello, can you cover a funeral in Kisumu next month including live streaming?', 1),
('photography', 'Kevin Mwangi', 'kevin@example.com', '0711000555', 'Photography booking', 'We need photography for my mother''s funeral in Othaya.', 1);
