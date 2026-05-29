# UniHub — Student Campus Portal

UniHub is a premium, high-performance student campus portal designed as a modern hub for university networking, academic organization, and peer-to-peer collaboration. Built with a stunning responsive glassmorphism UI, UniHub supports seamless account management, note-taking, skill exchange matchmaking, direct messaging, user profiles, and a robust administrative suite.

---

## 🚀 Fully Working Features

### 1. Secure Authentication & Session System
*   **Encrypted Accounts**: Registration and login protected by secure BCrypt password hashing (`password_hash()`).
*   **Session Hardening**: Sessions are protected with strict settings (`httponly`, `use_strict_mode`) and automated fingerprinting via User-Agent hashing to block session hijacking.
*   **Security Access Guards**: Route-based middlewares (`require_login()` and `require_admin()`) prevent unauthorized directory access.

### 2. High-Fidelity Responsive Dashboard
*   **Premium Glassmorphism Aesthetic**: Custom HSL color palettes, subtle transparency panels, gradients, and micro-animations styled with Vanilla CSS.
*   **Stateful Sidebar**: A collapsable smart navigation sidebar with responsive touch styling and lock/unlock state saved in the browser's local storage.
*   **Interactive Theme Switcher**: Instant theme swapping supporting Dark (default), Light, and Blue glass themes, persisted in local storage.
*   **Live Campus Counters**: Real-time counters showing total Saved Notes, Active Skills, Registered Students, and Messages.

### 3. Study Tools (Notes Module)
*   **Asynchronous Notebook**: Instantly add and delete notes without refreshing the page (AJAX Fetch API).
*   **Skeleton Loading States**: Seamless loading skeleton screens appear while retrieving database records.
*   **Content Validation**: Dynamic constraint checking (maximum 1000 characters) and real-time character counters.
*   **Smart Save Shortcut**: Save note using the `Ctrl + Enter` keyboard shortcut.

### 4. Smart Skill Exchange
*   **Skill Profiles**: Add skills you can teach and skills you want to learn (up to 10 entries per user).
*   **Automated Match Engine**: Real-time database matching query checks for mutual reciprocity and labels them as:
    *   `🔥 Perfect Match` (Reciprocal exchange found)
    *   `✅ They can teach you` (They offer what you need)
    *   `⭐ They want to learn from you` (They need what you offer)
*   **Search and Filter**: Instantly search all student listings by offered or needed skills with input debounce.
*   **Direct Outreach**: Quick-action messaging links directly from match cards.

### 5. Direct Messaging System
*   **Active Conversations Sidebar**: Lists active chat partners with user avatars and text previews of the last message exchanged.
*   **Chat Interface**: Elegant bubble dialogue bubbles with scroll-locking and message sent-time indicators.
*   **3-Second Auto-Polling**: Keeps chat active and updated in real-time by polling the database asynchronously.

### 6. Student Profile Management
*   **Personal Stats Tracker**: Displays registration date, total note count, and skill counts.
*   **Information Editor**: Update full name and bio (max 300 characters) with real-time character constraints.
*   **Avatar Image Engine**: Client-side file previewing and server-side upload handling for JPG, PNG, GIF, and WebP images (limited to 2MB).

### 7. Administrative Suite
*   **Management Dashboard**: Provides global system statistics (Total Users, Notes, Skills) to admin accounts.
*   **Tab-Based Management Panels**: Separate tables to view and moderate Users, Notes, and Skills.
*   **Role Management**: Promote normal users to admins or demote admins (with validation protecting the active user from self-demotion).
*   **Cascading Administrative Deletion**: Superusers can delete inappropriate notes, skills, or users. Deleting a user safely wipes all associated skills, notes, and messages in a cascading database operation.
*   **Glassmorphic Confirmation Modal**: Seamless, custom confirm dialogs replace default browser popups to ensure consistent styling.

---

## 🔮 Future Roadmap (What to Expect)

*   **P2P Student Marketplace**: A local exchange tab to buy, sell, or trade textbooks, study gear, electronics, and school supplies directly with other students on campus.
*   **Campus Events Hub**: An interactive calendar where student groups can schedule, promote, and register for upcoming campus workshops, social gatherings, and clubs.
*   **Group Community Channels**: Expanding the messaging system to include public group channels and chat rooms centered around academic majors, study groups, or campus topics.
*   **Live Match Alerts**: Push notifications and email alerts triggered when another student lists a skill that matches your offered or needed profile.
*   **WebSockets Integration**: Transitioning the 3-second AJAX polling messenger into a persistent, socket-based connection for instant, real-time message delivery.

---

## 🛠️ Tech Stack

| Layer | Technology |
| :--- | :--- |
| **Backend** | PHP 8.0+ (Vanilla, zero external frameworks) |
| **Database** | SQLite 3 (Accessed securely via PHP PDO) |
| **Frontend** | HTML5, Vanilla JavaScript (ES6) |
| **Styling** | Custom HSL-tailored CSS3 Variables, Glassmorphism, Micro-animations |
| **Fonts** | Google Fonts — Inter (400, 500, 600, 700, 800) |
| **Server Environment** | Apache via XAMPP |

---

## 📂 Project Directory Structure

```text
unihub/
├── index.php                 # Entry point — redirects guest to login or user to dashboard
├── login.php                 # User Sign In portal and session creation
├── register.php              # User Sign Up page (auto-promotes first user to admin)
├── dashboard.php             # Unified student application shell (all tabs on one page)
├── admin.php                 # Administrative control room dashboard
├── logout.php                # Destroys student session and redirects to sign-in page
├── database.db               # SQLite database file (created automatically on first load)
│
├── includes/
│   ├── db.php                # PDO connection engine and database schema definitions
│   └── auth.php              # Session checks, role-validation guards, and user login/logout handlers
│
├── modules/
│   ├── admin.php             # AJAX Endpoint: administrative fetch, delete, and role-toggle requests
│   ├── messages.php          # AJAX Endpoint: direct message retrieval, sending, and conversations list
│   ├── notes.php             # AJAX Endpoint: notebook note creation, fetch, and deletion
│   ├── profile.php           # AJAX Endpoint: profile info retrieval, updates, and avatar uploads
│   └── skills.php            # AJAX Endpoint: skills creation, deletion, search, and matching engine
│
└── assets/
    ├── css/
    │   ├── style.css         # Typography, layout layout configurations, and component styles
    │   └── enhancements.css   # Dark/Light/Blue theme styles, glassmorphism templates, and animations
    ├── js/
    │   ├── main.js           # Shared utilities (e.g. form validation helpers)
    │   ├── theme.js          # Switcher script to apply light, dark, or blue themes
    │   ├── sidebar.js        # Responsive sidebar state locking and local storage saving
    │   ├── notes.js          # AJAX controller and event hooks for Study Tools
    │   ├── skills.js         # Match visualizer, browse lists, and debounced search handlers
    │   ├── messages.js       # Conversation previews, bubble rendering, and AJAX chat polling
    │   ├── profile.js        # Bio counter, local avatar previewer, and multipart upload submitter
    │   └── admin.js          # Event delegation for delete requests and admin modal popup controller
    └── uploads/
        └── avatars/          # Directory hosting uploaded student avatar files (auto-created)
```

---

## ⚙️ Installation & Setup

1.  **Clone or Copy** the project directory into your local XAMPP web server directory:
    ```bash
    C:\xampp\htdocs\unihub\
    ```
2.  **Enable SQLite Extensions**: Make sure that PDO SQLite is enabled in your XAMPP installation. You can check your `php.ini` file and ensure the following line is uncommented:
    ```ini
    extension=pdo_sqlite
    ```
3.  **Start Apache**: Launch the XAMPP Control Panel and start the **Apache** server.
4.  **Launch the App**: Open your browser and navigate to:
    ```text
    http://localhost/unihub/
    ```
    *Note: The SQLite database file (`database.db`) will automatically generate in the root folder with all schemas on the first page load.*
5.  **Create Admin Credentials**: 
    The registration engine is configured to automatically set the role of the **very first registered user** to `admin`. 
    *   Go to `Register` and sign up. This user will immediately get full administrative permissions.
    *   Subsequent registrations will default to regular `user` accounts, which can then be promoted to admin by logging in to your primary admin account and managing them under the Admin Panel.

---

## 🛡️ Security Best Practices Implemented

*   **Prepared Statements**: Every single database transaction uses PDO parameterized inputs to completely mitigate SQL injection risks.
*   **HTML Escaping**: Student-generated content is sanitized and escaped with `htmlspecialchars()` before rendering to prevent Cross-Site Scripting (XSS).
*   **Session Regeneration**: On successful log in, `session_regenerate_id(true)` is called to replace the active session identifier and defend against session fixation attacks.
*   **Fingerprint Guards**: Active sessions validate a hashed combination of user attributes (User-Agent) to verify authentication consistency.
*   **JSON API Authentication**: AJAX endpoints return HTTP `401 Unauthorized` or `403 Forbidden` JSON payloads for unauthenticated API requests rather than returning raw HTML page redirects.
