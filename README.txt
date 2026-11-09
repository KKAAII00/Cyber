CYBERGUARD ACADEMY — COMPLETE STARTER PROJECT
=============================================

REQUIREMENTS
- XAMPP with PHP 8.1+ and MySQL/MariaDB
- Modern browser
- MySQL user allowed to create/import the database

INSTALLATION (WINDOWS / XAMPP)
1. Extract the CyberGuardAcademy_Complete folder into C:\xampp\htdocs\
2. Start Apache and MySQL in XAMPP Control Panel.
3. Open http://localhost/phpmyadmin/
4. Import database/schema.sql.
5. Open config/database.php and set your MySQL username/password if different.
6. Visit http://localhost/CyberGuardAcademy_Complete/
7. Register a student account.

LECTURER / ADMIN ACCESS
- New registrations are students by default.
- To promote an account to lecturer, run in phpMyAdmin:
  UPDATE cyberguard_academy.users SET role='lecturer' WHERE email='your-email@example.com';
- To promote an account to administrator:
  UPDATE cyberguard_academy.users SET role='admin' WHERE email='your-email@example.com';
- Sign out and sign back in after changing the role.
- Admins can manage roles at admin.php. Add an Admin link manually or visit that URL after login as admin.

FEATURES INCLUDED
- Landing page, registration, login, logout
- Student dashboard, agent profile and XP levels
- Six chapters / thirteen playable sample missions
- Interactive phishing inbox, password vault and forensic timeline games
- Cipher Lab, a clickable network-defense map, and an incident-response tabletop game
- Operation Shadow Protocol escalates through randomized Responder, Advanced and Expert question rounds
- Phishing, password security, network defense, digital forensics, cryptography and final incident response
- Explanation and score after every mission
- One-time first-completion XP rewards and attempt history
- Achievement badges
- Daily challenge selection
- Leaderboard
- Basic team room creation/joining, member list and combined score
- Lecturer progress dashboard
- Admin role management
- Responsive dark cybersecurity interface
- CSRF tokens, PDO prepared statements, password_hash/password_verify, session regeneration and role checks

IMPORTANT SCOPE NOTES
- This is a comprehensive educational starter, not a finished commercial game.
- The standard mission types use a reusable question-and-feedback engine. The added minigames are self-contained educational simulations; the network-defense map and incident-response scenario do not access real systems or encrypt files.
- Team rooms request browser camera/microphone permission on entry and share peer-to-peer video only with room members. Media is not recorded. Google STUN is used for network route discovery (not media); direct peer calls can expose network address information to participants. Use HTTPS outside localhost and consider a managed TURN relay for public deployment or restrictive networks.
- Learning activity records room/mission visits, each quiz answer with approximate response time, quiz submissions, game completions and copilot questions, and is visible only to the student on their dashboard. Quiz camera preview is optional, local to the browser and not recorded or analyzed. The application does not collect gaze, location or other device activity.
- The AI copilot uses Azure OpenAI and sends only the fictional mission/question context and the student's prompt. Configure the service before use; API keys must remain server-side.
- Character/equipment/background cosmetics are planned but not yet implemented as a full inventory system.
- Daily challenge picks one mission per calendar date. The same mission completion XP cannot be awarded repeatedly.
- For public deployment, add rate limiting, email verification, stronger audit logging, backup/restore procedures, privacy and retention policies, HTTPS configuration, and a full security review.

AI COPILOT CONFIGURATION
- Create an Azure OpenAI resource and deploy a chat-completions model.
- Set these environment variables for the PHP/Apache process, then restart it:
  AZURE_OPENAI_ENDPOINT=https://YOUR-RESOURCE.openai.azure.com
  AZURE_OPENAI_API_KEY=your-server-side-key
  AZURE_OPENAI_DEPLOYMENT=your-deployment-name
  AZURE_OPENAI_API_VERSION=2024-10-21
- On Windows, configure them in the environment for the account running XAMPP/PHP. Do not put keys in browser JavaScript, the database, or a committed file. If unset, the copilot displays a setup message.

SECURITY / SAFETY
All exercises use fictional data and simulated targets. Never use this game to scan, phish, attack or access real systems or accounts.
