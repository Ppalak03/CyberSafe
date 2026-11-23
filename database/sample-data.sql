-- CyberSafe sample scenarios. Import AFTER cybersafe.sql. Fictional brands and safe example domains only.
SET NAMES utf8mb4;
USE cybersafe_db;

INSERT INTO scenarios (category_id,title,content_type,content,difficulty,explanation,red_flags,safety_tip) VALUES ((SELECT id FROM categories WHERE name='Phishing Emails'),'Urgent bank account alert','email','{"from_name": "Northfield Bank Security", "from_email": "alert@northfield-bank-alerts.example", "subject": "Your account will be suspended today", "body": "Dear customer,\\n\\nWe detected unusual activity on your account. Your account will be suspended within 24 hours unless you confirm your details immediately.\\n\\nFailure to act will result in permanent loss of access.", "link_text": "Verify my account now", "link_target": "https://northfield-secure-login.example/verify"}','Easy','Real banks do not threaten to suspend your account and ask you to confirm details through an emailed link. The greeting is generic, and both the sender domain and the link domain differ from the bank''s official site.','Urgent threat of suspension
Generic greeting ("Dear customer")
Sender and link domains do not match the bank''s official domain
Asks you to confirm details through a link','Never use links in an unexpected alert. Open your bank''s app or type its address yourself.');
SET @sid = LAST_INSERT_ID();
INSERT INTO options (scenario_id,option_text,classification,is_correct) VALUES (@sid,'Safe','SAFE',0),(@sid,'Suspicious','SUSPICIOUS',0),(@sid,'Scam','SCAM',1);

INSERT INTO scenarios (category_id,title,content_type,content,difficulty,explanation,red_flags,safety_tip) VALUES ((SELECT id FROM categories WHERE name='Phishing Emails'),'Shared document notification','email','{"context": "You were not expecting any documents today.", "from_name": "Priya Nair (via DocShare)", "from_email": "no-reply@docshare-notify.example", "subject": "Priya shared \\"Q3 budget.xlsx\\" with you", "body": "Priya Nair has shared a document with you.\\n\\nOpen the document to view and comment.", "link_text": "Open document", "link_target": "https://docshare-notify.example/d/8f3k2"}','Medium','Document-sharing notices are real and common, so this is not automatically a scam. But you were not expecting it, the message has no personal note, and you cannot verify the service domain from the email alone. Confirm with Priya through another channel before opening it.','Unexpected document
Generic message with no personal note
Link goes to a service domain you cannot verify from the email','If a shared file surprises you, ask the sender in person or in your usual chat app before clicking.');
SET @sid = LAST_INSERT_ID();
INSERT INTO options (scenario_id,option_text,classification,is_correct) VALUES (@sid,'Safe','SAFE',0),(@sid,'Suspicious','SUSPICIOUS',1),(@sid,'Scam','SCAM',0);

INSERT INTO scenarios (category_id,title,content_type,content,difficulty,explanation,red_flags,safety_tip) VALUES ((SELECT id FROM categories WHERE name='Phishing Emails'),'Unusual password reset email','email','{"context": "You use BrightMail (official sign-in: accounts.brightmail.example). You did not request a password reset.", "from_name": "BrightMail Accounts", "from_email": "support@accounts-brightmail.example", "subject": "Password reset requested for your account", "body": "We received a request to reset your BrightMail password. If this was you, use the button below within 15 minutes.\\n\\nIf you did not request this, you can ignore this email.", "link_text": "Reset password", "link_target": "https://accounts-brightmail.example/reset/ab12"}','Hard','The calm wording, even the line telling you to ignore it, makes this convincing. The giveaway is the domain: the real service is accounts.brightmail.example, while this email uses accounts-brightmail.example, a separate look-alike site. You did not ask for a reset, so do not use the button.','Reset you did not request
Look-alike domain (hyphen instead of a dot)
Short time limit on the link','Compare domains character by character, and if you did not request a reset, sign in directly to check your account.');
SET @sid = LAST_INSERT_ID();
INSERT INTO options (scenario_id,option_text,classification,is_correct) VALUES (@sid,'Safe','SAFE',0),(@sid,'Suspicious','SUSPICIOUS',0),(@sid,'Scam','SCAM',1);

INSERT INTO scenarios (category_id,title,content_type,content,difficulty,explanation,red_flags,safety_tip) VALUES ((SELECT id FROM categories WHERE name='Phishing Emails'),'Monthly learning digest','email','{"context": "You subscribed to LearnHub''s newsletter last year.", "from_name": "LearnHub", "from_email": "news@learnhub.example", "subject": "Your October learning digest", "body": "Hi Asha,\\n\\nHere are three courses trending this month, based on topics you follow. No action is needed.\\n\\nHappy learning,\\nThe LearnHub team", "link_text": "Browse trending courses", "link_target": "https://www.learnhub.example/trending"}','Easy','This is an expected newsletter from a service you chose. The sender and link share the official learnhub.example domain, the message addresses you personally, and there is no urgency or request for personal data.','Expected newsletter from a service you subscribed to
Sender and link share the official domain
No urgency or request for personal details
Nothing to sign in to or pay','Even for familiar senders, visiting the site directly is a good habit whenever something feels off.');
SET @sid = LAST_INSERT_ID();
INSERT INTO options (scenario_id,option_text,classification,is_correct) VALUES (@sid,'Safe','SAFE',1),(@sid,'Suspicious','SUSPICIOUS',0),(@sid,'Scam','SCAM',0);

INSERT INTO scenarios (category_id,title,content_type,content,difficulty,explanation,red_flags,safety_tip) VALUES ((SELECT id FROM categories WHERE name='Phishing Emails'),'Overdue invoice attachment','email','{"context": "You do not recognise Harbor Supplies and have not ordered anything from them.", "from_name": "Accounts Department", "from_email": "billing@harbor-supplies-invoice.example", "subject": "Invoice #2291 overdue - second notice", "body": "Hello,\\n\\nPlease find attached invoice #2291. Payment is now overdue and late fees will apply.\\n\\nOpen the attachment to review and pay.", "attachment": "Invoice_2291.zip (ZIP archive, 48 KB)"}','Medium','An invoice from a company you have never dealt with, pressure about late fees and a ZIP attachment are classic signs of a malware or fake-payment email.','Vendor you have no relationship with
Pressure: overdue and late fees
ZIP attachment
Vague greeting with no company details','Do not open attachments you did not expect. Check with your finance team using a contact you already know.');
SET @sid = LAST_INSERT_ID();
INSERT INTO options (scenario_id,option_text,classification,is_correct) VALUES (@sid,'Safe','SAFE',0),(@sid,'Suspicious','SUSPICIOUS',0),(@sid,'Scam','SCAM',1);

INSERT INTO scenarios (category_id,title,content_type,content,difficulty,explanation,red_flags,safety_tip) VALUES ((SELECT id FROM categories WHERE name='Phishing Emails'),'Password expiry from IT','email','{"context": "You work at Corp Example. IT has sent expiry reminders before, but not in this format.", "from_name": "IT Service Desk", "from_email": "it-help@corp-example.test", "subject": "Your password expires in 3 days", "body": "Hello,\\n\\nYour network password expires in 3 days. To avoid interruption, update it on the staff portal.\\n\\nIT Service Desk", "link_text": "Update password", "link_target": "https://portal.corp-example.test/password"}','Hard','This could be genuine, but you cannot tell from the email. Sender names can be forged, password-expiry notices are a popular phishing theme, and the format is new to you. Reach the portal through your own bookmark or confirm with the help desk before acting.','Password-expiry prompts are a common phishing theme
Format differs from past IT notices
Sender addresses can be spoofed
Deadline pressure','Reach internal portals through your own bookmark, and confirm unusual IT notices with the help desk.');
SET @sid = LAST_INSERT_ID();
INSERT INTO options (scenario_id,option_text,classification,is_correct) VALUES (@sid,'Safe','SAFE',0),(@sid,'Suspicious','SUSPICIOUS',1),(@sid,'Scam','SCAM',0);

INSERT INTO scenarios (category_id,title,content_type,content,difficulty,explanation,red_flags,safety_tip) VALUES ((SELECT id FROM categories WHERE name='SMS Scams'),'Parcel redelivery fee','sms','{"sender": "PARCELGO", "timestamp": "Today, 9:41 AM", "body": "ParcelGo: Your package could not be delivered. Pay a Rs 30 redelivery fee within 24h or it will be returned: parcelgo-redeliver.example/p/7731", "link": "parcelgo-redeliver.example/p/7731"}','Easy','A small "redelivery fee" is a common trick to capture card details. The link is not the courier''s official site, the deadline creates pressure, and there is no tracking number or order detail.','Unexpected delivery fee
Urgent deadline
Link is not the courier''s official site
No order or tracking details','Track parcels using the courier''s official app or the tracking number from your order confirmation.');
SET @sid = LAST_INSERT_ID();
INSERT INTO options (scenario_id,option_text,classification,is_correct) VALUES (@sid,'Safe','SAFE',0),(@sid,'Suspicious','SUSPICIOUS',0),(@sid,'Scam','SCAM',1);

INSERT INTO scenarios (category_id,title,content_type,content,difficulty,explanation,red_flags,safety_tip) VALUES ((SELECT id FROM categories WHERE name='SMS Scams'),'Bank login code','sms','{"context": "You just tried to log in to the Northfield Bank app.", "sender": "NFBANK", "timestamp": "Today, 2:15 PM", "body": "Northfield Bank: Your login code is ●●●●●●. It expires in 5 minutes. Never share this code with anyone, including bank staff."}','Medium','This code arrived right after you started a login. It has no link, does not ask you to call anyone and warns you not to share it. Just do not read it out to anyone.','Arrived right after you started a login
No link or request to call
Explicit warning not to share the code
Short expiry','A code you did not request is a warning sign. A code you just requested is expected, and it must never be shared.');
SET @sid = LAST_INSERT_ID();
INSERT INTO options (scenario_id,option_text,classification,is_correct) VALUES (@sid,'Safe','SAFE',1),(@sid,'Suspicious','SUSPICIOUS',0),(@sid,'Scam','SCAM',0);

INSERT INTO scenarios (category_id,title,content_type,content,difficulty,explanation,red_flags,safety_tip) VALUES ((SELECT id FROM categories WHERE name='SMS Scams'),'Lucky draw prize','sms','{"sender": "WIN-REWARD", "timestamp": "Yesterday, 4:03 PM", "body": "CONGRATULATIONS! You have won a Rs 50,000 shopping voucher in our lucky draw. Claim now: claim-voucher-prize.example/win", "link": "claim-voucher-prize.example/win"}','Easy','You cannot win a draw you never entered. Prize messages with unknown links aim to collect personal or payment details.','You never entered a draw
Prize far too good to be true
Unknown link
Urgency to claim','If you did not enter it, you did not win it. Delete and block.');
SET @sid = LAST_INSERT_ID();
INSERT INTO options (scenario_id,option_text,classification,is_correct) VALUES (@sid,'Safe','SAFE',0),(@sid,'Suspicious','SUSPICIOUS',0),(@sid,'Scam','SCAM',1);

INSERT INTO scenarios (category_id,title,content_type,content,difficulty,explanation,red_flags,safety_tip) VALUES ((SELECT id FROM categories WHERE name='SMS Scams'),'New number from family','sms','{"context": "You have not heard anything about a family member changing phones.", "sender": "+00 5550 9876", "timestamp": "Yesterday, 6:20 PM", "body": "Hi Mum, this is my new number, my old phone broke. Can you send Rs 8,000 right now? I will explain later, please don''t call."}','Medium','Scammers pretend to be a relative with a new number and ask for urgent money. The request not to call is meant to stop you from checking.','Unknown number claiming to be family
Urgent money request
Asks you not to call
No way to verify the sender','Call your family member on the number you already have before sending any money.');
SET @sid = LAST_INSERT_ID();
INSERT INTO options (scenario_id,option_text,classification,is_correct) VALUES (@sid,'Safe','SAFE',0),(@sid,'Suspicious','SUSPICIOUS',0),(@sid,'Scam','SCAM',1);

INSERT INTO scenarios (category_id,title,content_type,content,difficulty,explanation,red_flags,safety_tip) VALUES ((SELECT id FROM categories WHERE name='SMS Scams'),'Library fine reminder','sms','{"context": "You do have a library card, but you are not aware of anything overdue, and the library normally emails reminders.", "sender": "CITYLIB", "timestamp": "Today, 11:02 AM", "body": "City Library: 1 item overdue. Fine due Rs 20. Pay here: shorturl.example/q8Zk to avoid suspension of borrowing.", "link": "shorturl.example/q8Zk"}','Hard','The message is plausible, so it may be real, but the shortened link hides its destination, the channel differs from usual, and there is a threat attached to a tiny fee. Check your library account directly instead.','Shortened link hides the destination
Small fee combined with a threat
Different channel from the library''s usual reminders
Cannot be verified from the message','Log in to the official site yourself, or ask at the library, rather than using a link in a text.');
SET @sid = LAST_INSERT_ID();
INSERT INTO options (scenario_id,option_text,classification,is_correct) VALUES (@sid,'Safe','SAFE',0),(@sid,'Suspicious','SUSPICIOUS',1),(@sid,'Scam','SCAM',0);

INSERT INTO scenarios (category_id,title,content_type,content,difficulty,explanation,red_flags,safety_tip) VALUES ((SELECT id FROM categories WHERE name='Fake Job Offers'),'Work-from-home data entry','job_offer','{"company": "QuickDesk Solutions", "job_title": "Data Entry Executive (Work From Home)", "salary": "Rs 45,000 per month, no experience needed", "process": "Pay registration, receive login details, start typing forms the same day.", "requests": ["Rs 1,500 registration and training fee", "Payment by UPI to a personal account"], "message": "Congratulations! You are selected without an interview. Complete registration today to secure the position."}','Easy','Real employers do not charge you to get hired. Being selected with no interview, high pay for no skills and a payment to a personal account are all hallmarks of a job scam.','Asks you to pay to get a job
Hired with no interview
Unrealistic pay for no skills
Payment to a personal account','Legitimate employers never charge application, training or equipment fees.');
SET @sid = LAST_INSERT_ID();
INSERT INTO options (scenario_id,option_text,classification,is_correct) VALUES (@sid,'Safe','SAFE',0),(@sid,'Suspicious','SUSPICIOUS',0),(@sid,'Scam','SCAM',1);

INSERT INTO scenarios (category_id,title,content_type,content,difficulty,explanation,red_flags,safety_tip) VALUES ((SELECT id FROM categories WHERE name='Fake Job Offers'),'Interview invitation','job_offer','{"company": "Brightwave Systems", "job_title": "Junior Software Tester", "salary": "Discussed at interview, within the advertised range", "process": "30-minute video interview Thursday 3 PM. Track your application on careers.brightwave.example.", "requests": ["Confirm your slot by replying", "Bring your updated CV (already submitted)"], "contact": "recruiting@brightwave.example", "message": "Hi Asha, thanks for applying through our careers portal. We would like to invite you to an interview with our QA lead. You can verify this application in your portal account."}','Medium','You applied yourself, the recruiter writes from the company domain, the process can be verified on the official portal, and nobody asks for money or sensitive documents.','Application you made yourself
Recruiter email on the company domain
No money or sensitive documents requested
Process verifiable on the official portal','Verify through the company''s own careers site, which is exactly what this message invites you to do.');
SET @sid = LAST_INSERT_ID();
INSERT INTO options (scenario_id,option_text,classification,is_correct) VALUES (@sid,'Safe','SAFE',1),(@sid,'Suspicious','SUSPICIOUS',0),(@sid,'Scam','SCAM',0);

INSERT INTO scenarios (category_id,title,content_type,content,difficulty,explanation,red_flags,safety_tip) VALUES ((SELECT id FROM categories WHERE name='Fake Job Offers'),'Like-and-earn tasks','job_offer','{"company": "Global Reach Digital", "job_title": "Social Media Task Associate", "salary": "Rs 3,000 to Rs 5,000 per day", "process": "Like and rate videos, then deposit a small amount to unlock higher-paying tasks. Withdraw anytime.", "requests": ["Deposit money to unlock tasks", "Join a private chat group"], "contact": "Message from an unknown number on a chat app", "message": "Earn daily by simply liking videos. Start in 10 minutes!"}','Easy','A "job" that asks you to deposit money before you can earn is a scam. Early small payouts are used to build trust before larger deposits are taken.','Pay-to-unlock task structure
Huge pay for trivial work
Unprompted contact on a chat app
Pressure to start quickly','Never deposit money to unlock earnings from any online job.');
SET @sid = LAST_INSERT_ID();
INSERT INTO options (scenario_id,option_text,classification,is_correct) VALUES (@sid,'Safe','SAFE',0),(@sid,'Suspicious','SUSPICIOUS',0),(@sid,'Scam','SCAM',1);

INSERT INTO scenarios (category_id,title,content_type,content,difficulty,explanation,red_flags,safety_tip) VALUES ((SELECT id FROM categories WHERE name='Fake Job Offers'),'Vague high-paying role','job_offer','{"company": "Meridian Growth Partners", "job_title": "Business Development Associate", "salary": "Rs 80,000 to Rs 1,20,000 per month", "process": "Brief chat on Telegram, then onboarding.", "requests": ["Move the conversation to Telegram", "Share your personal phone number"], "contact": "Direct message on a networking site", "message": "We reviewed your profile and think you are a great fit for a high-growth role. Details are easier to share on Telegram."}','Hard','No money is requested yet, so it is not clearly a scam, but several warning signs are present: vague role and company details, unusually high pay in a first message, and pressure to leave the platform. Ask for the official job posting and company website first.','Vague role and company details
Unusually high pay for a first message
Pushes you off the original platform
No verifiable company website mentioned','Ask for the official posting and check the company independently before sharing anything.');
SET @sid = LAST_INSERT_ID();
INSERT INTO options (scenario_id,option_text,classification,is_correct) VALUES (@sid,'Safe','SAFE',0),(@sid,'Suspicious','SUSPICIOUS',1),(@sid,'Scam','SCAM',0);

INSERT INTO scenarios (category_id,title,content_type,content,difficulty,explanation,red_flags,safety_tip) VALUES ((SELECT id FROM categories WHERE name='Fake Job Offers'),'Internship equipment deposit','job_offer','{"company": "Nexa Interns Hub", "job_title": "Marketing Intern", "salary": "Rs 15,000 stipend", "process": "Offer letter issued after payment of a laptop security deposit, refunded after 3 months.", "requests": ["Rs 6,000 refundable equipment deposit", "Scan of your ID document"], "message": "Your internship is confirmed. Pay the equipment deposit today so we can dispatch your laptop."}','Medium','Calling the fee "refundable" lowers suspicion, but a deposit demanded before you start is a typical advance-fee scam. Asking for ID documents before you can verify the company adds identity-theft risk.','Deposit demanded up front
"Refundable" promise to reduce suspicion
ID documents requested before verification
Rushed timeline','Research the company and speak to current staff or your college placement office before paying or sharing ID.');
SET @sid = LAST_INSERT_ID();
INSERT INTO options (scenario_id,option_text,classification,is_correct) VALUES (@sid,'Safe','SAFE',0),(@sid,'Suspicious','SUSPICIOUS',0),(@sid,'Scam','SCAM',1);

INSERT INTO scenarios (category_id,title,content_type,content,difficulty,explanation,red_flags,safety_tip) VALUES ((SELECT id FROM categories WHERE name='Suspicious URLs'),'Bank link in a text message','url','{"displayed_url": "https://northfie1d-bank.example/login", "claimed_destination": "Northfield Bank online banking", "context": "Received in a text saying your card is blocked.", "clues": ["Registered domain: northfie1d-bank.example", "Official bank site you know: northfield-bank.example", "Connection: HTTPS"]}','Easy','Look closely: "northfie1d" uses the digit 1 instead of the letter l. HTTPS only means the connection is encrypted, not that the site is genuine. The link arrived through an unexpected text.','Look-alike spelling (digit 1 for letter l)
Arrived through an unexpected text
Domain differs from the official bank site','Type your bank''s address yourself or use its app. Never log in through a link in a message.');
SET @sid = LAST_INSERT_ID();
INSERT INTO options (scenario_id,option_text,classification,is_correct) VALUES (@sid,'Safe','SAFE',0),(@sid,'Suspicious','SUSPICIOUS',0),(@sid,'Scam','SCAM',1);

INSERT INTO scenarios (category_id,title,content_type,content,difficulty,explanation,red_flags,safety_tip) VALUES ((SELECT id FROM categories WHERE name='Suspicious URLs'),'Course page from site search','url','{"displayed_url": "https://www.learnhub.example/courses/python-basics", "claimed_destination": "LearnHub Python Basics course page", "context": "You opened this from LearnHub''s own search results.", "clues": ["Registered domain: learnhub.example", "Official site you know: learnhub.example", "Connection: HTTPS"]}','Easy','The registered domain exactly matches the official site and you reached it from the site itself, not from a message.','Domain exactly matches the official site
You reached it from the site''s own search
HTTPS connection','How you arrived matters as much as how the link looks.');
SET @sid = LAST_INSERT_ID();
INSERT INTO options (scenario_id,option_text,classification,is_correct) VALUES (@sid,'Safe','SAFE',1),(@sid,'Suspicious','SUSPICIOUS',0),(@sid,'Scam','SCAM',0);

INSERT INTO scenarios (category_id,title,content_type,content,difficulty,explanation,red_flags,safety_tip) VALUES ((SELECT id FROM categories WHERE name='Suspicious URLs'),'Subscription payment failed','url','{"displayed_url": "https://learnhub.example.account-verify.example/signin", "claimed_destination": "LearnHub sign-in", "context": "Link in an email saying your subscription payment failed.", "clues": ["Registered domain (the part just before the final .example): account-verify.example", "\\"learnhub.example\\" appears only as a subdomain at the start", "Connection: HTTPS"]}','Medium','Read domains from right to left. The real owner is the part just before the final ending, which here is account-verify.example. Putting a familiar name at the start is a trick to look legitimate.','Familiar brand used only as a subdomain
Real registered domain is unrelated
Unexpected payment-failure email','To find who owns a link, read the domain from right to left up to the last dot.');
SET @sid = LAST_INSERT_ID();
INSERT INTO options (scenario_id,option_text,classification,is_correct) VALUES (@sid,'Safe','SAFE',0),(@sid,'Suspicious','SUSPICIOUS',0),(@sid,'Scam','SCAM',1);

INSERT INTO scenarios (category_id,title,content_type,content,difficulty,explanation,red_flags,safety_tip) VALUES ((SELECT id FROM categories WHERE name='Suspicious URLs'),'Shortened link in team chat','url','{"displayed_url": "https://go.corp-example.test/q3-report", "claimed_destination": "Q3 report on the company shared drive", "context": "Posted by a colleague in team chat. You have not seen this short-link format before.", "clues": ["Registered domain: corp-example.test", "Shortened link: final destination hidden", "Posted by a real colleague account, but accounts can be compromised"]}','Hard','The domain looks like your company''s, but the destination is hidden and the format is new to you. A colleague''s compromised account can post malicious links. Ask the colleague or open the report from the shared drive directly.','Destination hidden behind a short link
Unfamiliar link format
Colleague accounts can be compromised','When in doubt, ask the person or open the file through the official drive instead.');
SET @sid = LAST_INSERT_ID();
INSERT INTO options (scenario_id,option_text,classification,is_correct) VALUES (@sid,'Safe','SAFE',0),(@sid,'Suspicious','SUSPICIOUS',1),(@sid,'Scam','SCAM',0);

INSERT INTO scenarios (category_id,title,content_type,content,difficulty,explanation,red_flags,safety_tip) VALUES ((SELECT id FROM categories WHERE name='Suspicious URLs'),'Unencrypted sign-in page','url','{"displayed_url": "http://cloudstore-example.test/login", "claimed_destination": "CloudStore file sign-in", "context": "A bookmark you saved last year.", "clues": ["Registered domain: cloudstore-example.test", "Connection: HTTP (not encrypted)", "Browser shows \\"Not secure\\""]}','Medium','The domain may be the real one, but a sign-in page over plain HTTP means anything you type can be intercepted. Do not enter credentials until you reach the HTTPS version from a trusted source.','Sign-in over unencrypted HTTP
Browser "Not secure" warning
Old bookmark may be outdated or altered','Look for HTTPS before entering a password, and treat browser warnings seriously.');
SET @sid = LAST_INSERT_ID();
INSERT INTO options (scenario_id,option_text,classification,is_correct) VALUES (@sid,'Safe','SAFE',0),(@sid,'Suspicious','SUSPICIOUS',1),(@sid,'Scam','SCAM',0);

INSERT INTO scenarios (category_id,title,content_type,content,difficulty,explanation,red_flags,safety_tip) VALUES ((SELECT id FROM categories WHERE name='Fake Login Pages'),'Card verification page','login_page','{"context": "You reached this page from an email link.", "brand": "Northfield Bank", "domain": "https://nf-bank-login.example/secure", "heading": "Verify your card", "fields": ["Card number", "Expiry date", "Card PIN", "One-time code"], "button": "Confirm", "notice": "Enter your PIN to avoid card suspension."}','Easy','Banks never ask for your PIN or a one-time code on a web form. The domain does not match the bank''s official site, and the page uses a suspension threat to rush you.','Asks for card PIN
Asks for a one-time code
Unofficial domain
Suspension threat','Your PIN and one-time codes are for you only. No genuine page asks for them together.');
SET @sid = LAST_INSERT_ID();
INSERT INTO options (scenario_id,option_text,classification,is_correct) VALUES (@sid,'Safe','SAFE',0),(@sid,'Suspicious','SUSPICIOUS',0),(@sid,'Scam','SCAM',1);

INSERT INTO scenarios (category_id,title,content_type,content,difficulty,explanation,red_flags,safety_tip) VALUES ((SELECT id FROM categories WHERE name='Fake Login Pages'),'Course site sign-in','login_page','{"context": "You typed learnhub.example into your browser yourself.", "brand": "LearnHub", "domain": "https://www.learnhub.example/account/login", "heading": "Sign in to LearnHub", "fields": ["Email", "Password"], "button": "Sign in", "notice": ""}','Easy','You reached the official domain yourself, and the page asks only for what a normal sign-in needs: an email and a password.','Official domain you typed yourself
Only email and password requested
No urgency or threats','Typing the address yourself or using a bookmark is the safest way to reach a sign-in page.');
SET @sid = LAST_INSERT_ID();
INSERT INTO options (scenario_id,option_text,classification,is_correct) VALUES (@sid,'Safe','SAFE',1),(@sid,'Suspicious','SUSPICIOUS',0),(@sid,'Scam','SCAM',0);

INSERT INTO scenarios (category_id,title,content_type,content,difficulty,explanation,red_flags,safety_tip) VALUES ((SELECT id FROM categories WHERE name='Fake Login Pages'),'Suspicious sign-in warning','login_page','{"context": "Opened from an email warning about a \\"suspicious sign-in\\" to your BrightMail account.", "brand": "BrightMail", "domain": "https://brightmail-support.example/signin", "heading": "Sign in to BrightMail", "fields": ["Email", "Password", "Recovery code", "One-time code"], "button": "Sign in", "notice": "For security, enter your recovery code and the code we just sent you."}','Hard','A genuine sign-in does not ask for your recovery code and a one-time code at the same time. The domain is not the service''s own, and the page was reached from a scare email. Gathering everything at once lets attackers take over the account.','Recovery code requested
Password and one-time code on one page
Domain is not the official BrightMail domain
Reached from a scare email','Recovery codes are a last resort. Never type them into a page you reached from a message.');
SET @sid = LAST_INSERT_ID();
INSERT INTO options (scenario_id,option_text,classification,is_correct) VALUES (@sid,'Safe','SAFE',0),(@sid,'Suspicious','SUSPICIOUS',0),(@sid,'Scam','SCAM',1);

INSERT INTO scenarios (category_id,title,content_type,content,difficulty,explanation,red_flags,safety_tip) VALUES ((SELECT id FROM categories WHERE name='Fake Login Pages'),'Session expired pop-up','login_page','{"context": "This appeared in a pop-up while you were reading an email in your browser. You had no work page open.", "brand": "Corp Example SSO", "domain": "https://sso.corp-example.test/login", "heading": "Session expired", "fields": ["Work email", "Password"], "button": "Sign in", "notice": "Your session expired. Sign in again to continue."}','Medium','The domain looks plausible, but you had no session to expire and the pop-up came from an email page. Close it, then open your work portal yourself and sign in there.','Pop-up triggered while reading an email
No session that could have expired
Cannot be verified from within the pop-up','If a sign-in prompt appears unexpectedly, close it and navigate to the real site yourself.');
SET @sid = LAST_INSERT_ID();
INSERT INTO options (scenario_id,option_text,classification,is_correct) VALUES (@sid,'Safe','SAFE',0),(@sid,'Suspicious','SUSPICIOUS',1),(@sid,'Scam','SCAM',0);

INSERT INTO scenarios (category_id,title,content_type,content,difficulty,explanation,red_flags,safety_tip) VALUES ((SELECT id FROM categories WHERE name='Social Engineering'),'Fake IT support call','chat','{"context": "Unexpected phone call. You did not contact IT.", "messages": [{"sender": "Caller", "text": "Hello, this is Rahul from IT Support. We have detected a virus on your computer."}, {"sender": "You", "text": "Oh no, what should I do?"}, {"sender": "Caller", "text": "Install this remote-access tool and read me the verification code on your phone so I can fix it right now."}]}','Easy','Unsolicited tech-support calls are a classic scam. Remote access plus a verification code would give the caller control of your device and accounts.','Unsolicited call
Scare tactic (virus)
Asks for remote access
Asks for a verification code','Hang up and contact IT through a number you already know.');
SET @sid = LAST_INSERT_ID();
INSERT INTO options (scenario_id,option_text,classification,is_correct) VALUES (@sid,'Safe','SAFE',0),(@sid,'Suspicious','SUSPICIOUS',0),(@sid,'Scam','SCAM',1);

INSERT INTO scenarios (category_id,title,content_type,content,difficulty,explanation,red_flags,safety_tip) VALUES ((SELECT id FROM categories WHERE name='Social Engineering'),'Director needs gift cards','chat','{"context": "The message came from a personal email address you do not recognise, using your director''s name.", "messages": [{"sender": "Meera (Director)", "text": "Are you at your desk? I am in a meeting and cannot talk."}, {"sender": "You", "text": "Yes, what do you need?"}, {"sender": "Meera (Director)", "text": "Buy 5 gift cards of Rs 2,000 for a client gift. Send me the codes here. I will reimburse you today. Keep it confidential."}]}','Medium','Impersonating a senior person, asking for secrecy and requesting gift card codes are all hallmarks of boss-impersonation fraud.','Unfamiliar email address using a boss''s name
Gift card codes requested
Cannot talk, so you cannot verify
Told to keep it confidential','Verify any unusual money request by calling the person on a known number.');
SET @sid = LAST_INSERT_ID();
INSERT INTO options (scenario_id,option_text,classification,is_correct) VALUES (@sid,'Safe','SAFE',0),(@sid,'Suspicious','SUSPICIOUS',0),(@sid,'Scam','SCAM',1);

INSERT INTO scenarios (category_id,title,content_type,content,difficulty,explanation,red_flags,safety_tip) VALUES ((SELECT id FROM categories WHERE name='Social Engineering'),'Colleague asks for meeting notes','chat','{"context": "Rahul is on your team and was in that meeting. The message came through your normal team chat.", "messages": [{"sender": "Rahul (Team)", "text": "Hi Asha, could you share the notes from yesterday''s planning meeting?"}, {"sender": "You", "text": "Sure! Sending the link to the team folder."}, {"sender": "Rahul (Team)", "text": "Thanks, that works. I will add my action items."}]}','Medium','This is an ordinary work request from a known colleague in the usual channel. The notes belong in the team folder, nothing sensitive is requested and there is no pressure.','Known colleague in the usual channel
Relevant, ordinary request
No pressure, money or credentials involved','Context matters: normal requests through normal channels are fine, but stay alert when any of those change.');
SET @sid = LAST_INSERT_ID();
INSERT INTO options (scenario_id,option_text,classification,is_correct) VALUES (@sid,'Safe','SAFE',1),(@sid,'Suspicious','SUSPICIOUS',0),(@sid,'Scam','SCAM',0);

INSERT INTO scenarios (category_id,title,content_type,content,difficulty,explanation,red_flags,safety_tip) VALUES ((SELECT id FROM categories WHERE name='Social Engineering'),'Friend asks for a code','chat','{"context": "You have just received a text with a login code you did not request.", "messages": [{"sender": "Sam (Friend)", "text": "Hey! I think a code was sent to your phone by mistake, can you send it to me?"}, {"sender": "You", "text": "Which code?"}, {"sender": "Sam (Friend)", "text": "A 6-digit code from a verification text. I am locked out, quick!"}]}','Easy','This is likely a hijacked friend account. The code you received was probably triggered by the attacker trying to sign in as you. Sharing it hands over your account.','Request for a verification code
Urgent pressure
Matches a code you did not request','Never share verification codes, even with friends. Check with them by phone or in person.');
SET @sid = LAST_INSERT_ID();
INSERT INTO options (scenario_id,option_text,classification,is_correct) VALUES (@sid,'Safe','SAFE',0),(@sid,'Suspicious','SUSPICIOUS',0),(@sid,'Scam','SCAM',1);

INSERT INTO scenarios (category_id,title,content_type,content,difficulty,explanation,red_flags,safety_tip) VALUES ((SELECT id FROM categories WHERE name='Social Engineering'),'Vendor changes bank details','chat','{"context": "Harbor Supplies is a real vendor you have paid before, but you have never spoken to Anita and the request arrived by email.", "messages": [{"sender": "Anita (Harbor Supplies)", "text": "Hi Asha, we have changed banks. Please send this month''s payment to the new account details attached."}, {"sender": "You", "text": "Can you confirm this with someone from your finance team?"}, {"sender": "Anita (Harbor Supplies)", "text": "Sure, but the invoice is due tomorrow so please do it today."}]}','Hard','Bank-detail changes are a well-known fraud route. The vendor is real, so this may be genuine, but a new contact, a deadline and avoiding verification are warning signs. Confirm using a phone number from your records.','Change of bank details by email
Unfamiliar contact
Deadline pressure
Deflects your verification request','Always verify payment detail changes using contact details you already hold, never those in the request.');
SET @sid = LAST_INSERT_ID();
INSERT INTO options (scenario_id,option_text,classification,is_correct) VALUES (@sid,'Safe','SAFE',0),(@sid,'Suspicious','SUSPICIOUS',1),(@sid,'Scam','SCAM',0);
