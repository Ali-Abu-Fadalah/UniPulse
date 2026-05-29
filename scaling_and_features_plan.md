# UniHub 10x Scale & Architecture Roadmap

This developer plan outlines the optimizations, architecture changes, and visual additions to scale UniHub to support **10,000+ active users** with sub-100ms response times.

---

## 1. Database Scaling (MySQL Optimization)

As user counts grow, database lookups are the primary bottleneck. We must transition from default local setup to an optimized database tier.

### Indexing Strategies
Currently, columns are created without targeted search indexes. We must add:
- **Composite Indexes:**
  - `messages`: INDEX (`sender_id`, `receiver_id`, `created_at`) - Speeds up private chats.
  - `event_rsvps`: INDEX (`event_id`, `user_id`) - Speeds up RSVP listings.
- **Search Indexes (Full-Text):**
  - Add `FULLTEXT` index to `products.title`, `products.description`, `events.title`, and `skills.offered` / `skills.needed` to replace slow database-scanning `LIKE %query%` searches with fast index matches.

### Connection Pooling & Read-Write Splitting
- **Persistent Connections:** Change PDO parameters to use persistent database connections (`PDO::ATTR_PERSISTENT => true`) to avoid the overhead of reopening TCP connections on every script load.
- **Replica Nodes:** As read operations scale, introduce a Primary database (handles `INSERT/UPDATE/DELETE`) and multiple Read Replicas (handle `SELECT`).

---

## 2. Caching Layer (Redis)

Under high load, hitting the database for session data and static configuration on every page request causes CPU exhaustion.

- **Redis Session Driver:** Move PHP sessions from standard disk files to Redis. This prevents file locking bottlenecks on high concurrent loads.
- **Query Cache:** Cache query results that change infrequently (e.g., active event list, user profiles, skill listings) using a TTL (Time-to-Live) pattern.
- **Transient Data (Read Counts / Badges):** Store notification count badges and online user lists directly in Redis memory rather than querying the SQL tables repeatedly.

---

## 3. Real-Time WebSockets Architecture

AJAX Polling (e.g., pulling notifications or new chat messages every 10 seconds) creates a massive database query queue under load (1,000 users = 100 queries per second just for polling!).

- **Action:** Transition to a permanent WebSockets connection.
- **Tech Stack:** Introduce a light **Node.js/Socket.io** helper daemon or use PHP-compatible WebSocket libraries (e.g. **Ratchet** or **Swoole**).
- **Benefit:** Real-time event broadcasting. When User A posts a message, the server pushes it directly to User B's open socket. Zero database polling required.

---

## 4. Modernizing the Codebase (Laravel MVC Migration)

Procedural PHP scripts can lead to code duplication and are harder to maintain in large teams. Upgrading to a modern framework is the single best decision for long-term scalability.

- **Action:** Rewrite the UniHub backend into **Laravel**.
- **Why Laravel?**
  - **Laravel Horizon & Queues:** Send notification emails, resize avatars, and compile matches asynchronously in background queues so the web page response is instantaneous.
  - **Eloquent ORM:** Out-of-the-box support for database migrations, relationships, eager loading, and query optimization.
  - **Sanctum & API Resource Routing:** Clean API controllers separated from the frontend.

---

## 5. Front-End Assets & Delivery Tuning

- **Asset Compilation (Vite):** Use a bundler to minify, tree-shake, and combine JavaScript and CSS files, reducing network load times.
- **Image Compression Pipeline:** Integrate a backend image processing library (like PHP Intervention Image) to resize uploaded avatars and marketplace photos to web-optimized formats (WebP) upon upload.
- **CDN (Content Delivery Network):** Store all uploaded images and static assets in an object storage container (e.g. AWS S3) fronted by a CDN (Cloudflare) to take the static file bandwidth load completely off your application server.

---

## 6. Hosting Infrastructure & DevOps

To achieve high availability and absolute uptime:
- **Load Balancing (NGINX / HAProxy):** Distribute incoming requests across multiple small PHP application servers.
- **Containerization (Docker):** Wrap the PHP-FPM, Nginx, and Tailwind compilation into lightweight containers for instant scalability.
- **Autoscaling Groups:** Configure hosting providers (AWS EC2 / DigitalOcean Kubernetes) to automatically spawn new application containers when CPU load exceeds 70%.
