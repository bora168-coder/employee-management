# Employee Management System

A full-stack web application for managing employee records, built with PHP and MySQL, containerized with Docker, and deployable on Kubernetes.

---

## Tech Stack

| Layer         | Technology              |
| ------------- | ----------------------- |
| Backend       | PHP 8.2 (Apache)        |
| Database      | MySQL 8.0               |
| Container     | Docker + Docker Compose |
| Orchestration | Kubernetes (Minikube)   |
| Tunnel        | Cloudflared             |

---

## Features

- Add new employees
- View employee list with search
- Edit employee details
- Delete employees
- Form validation (required fields, email format, duplicate checks, salary, hire date)
- SQL injection protection via prepared statements

---

## Project Structure

```
employee-management/
├── index.php           # Employee list + search
├── create.php          # Add employee form
├── view.php            # View single employee
├── edit.php            # Edit employee form
├── delete.php          # Delete employee
├── db.php              # Database connection
├── Dockerfile
├── docker-compose.yml
├── README.md
└── k8s/
    ├── mysql-secret.yaml
    ├── mysql-pvc.yaml
    ├── mysql-deployment.yaml
    ├── mysql-service.yaml
    ├── php-configmap.yaml
    ├── php-deployment.yaml
    └── php-service.yaml
```

---

## Database

**Database:** `exam_db`  
**Table:** `employees`

```sql
CREATE TABLE IF NOT EXISTS employees (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    employee_code   VARCHAR(30)     NOT NULL UNIQUE,
    first_name      VARCHAR(100)    NOT NULL,
    last_name       VARCHAR(100)    NOT NULL,
    email           VARCHAR(150)    NOT NULL UNIQUE,
    phone           VARCHAR(30),
    department      VARCHAR(100)    NOT NULL,
    position        VARCHAR(100)    NOT NULL,
    salary          DECIMAL(10, 2)  DEFAULT 0.00,
    hire_date       DATE            NOT NULL,
    status          ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

---

## Run with Docker

```bash
# Build and start
docker compose up -d --build

# View logs
docker compose logs --tail=100

# Stop
docker compose down
```

App available at: `http://localhost:8080`

---

## Run with Kubernetes (Minikube)

```bash
# Start Minikube
minikube start

# Load the local image
minikube image load employee-management-system:latest

# Apply all manifests
kubectl apply -f k8s/

# Check status
kubectl get deployments
kubectl get pods
kubectl get services

# Get the app URL
minikube service php-service --url
```

---

## Cloudflared Tunnel (Custom Domain)

> **User action required** — you must supply your own Cloudflare credentials.

1. Install Cloudflared: https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/get-started/
2. Log in:
   ```bash
   cloudflared tunnel login
   ```
3. Create a tunnel:
   ```bash
   cloudflared tunnel create employee-management
   ```
4. Configure `~/.cloudflared/config.yml`:
   ```yaml
   tunnel: <YOUR_TUNNEL_ID>
   credentials-file: /root/.cloudflared/<YOUR_TUNNEL_ID>.json
   ingress:
     - hostname: <YOUR_DOMAIN>
       service: http://localhost:80
     - service: http_status:404
   ```
5. Add a DNS record in Cloudflare pointing `<YOUR_DOMAIN>` to the tunnel.
6. Start the tunnel:
   ```bash
   cloudflared tunnel run employee-management
   ```

Never commit tunnel tokens, API keys, or credential files.

---

## Environment Variables

| Variable              | Default    | Description         |
| --------------------- | ---------- | ------------------- |
| `DB_HOST`             | `mysql`    | MySQL host          |
| `DB_PORT`             | `3306`     | MySQL port          |
| `DB_NAME`             | `exam_db`  | Database name       |
| `DB_USER`             | `app_user` | Database user       |
| `DB_PASSWORD`         | _(secret)_ | Database password   |
| `MYSQL_ROOT_PASSWORD` | _(secret)_ | MySQL root password |

---

## Verification Queries

```sql
-- List all employees
SELECT * FROM employees ORDER BY id DESC;

-- Count by department
SELECT department, COUNT(*) AS total FROM employees GROUP BY department;

-- Search by name
SELECT * FROM employees WHERE first_name LIKE '%John%' OR last_name LIKE '%John%';
```

---

## Submission Checklist

- [ ] Source code uploaded
- [ ] Docker screenshots captured
- [ ] Kubernetes screenshots captured
- [ ] Minikube screenshots captured
- [ ] Cloudflared screenshots captured
- [ ] Browser screenshot with HTTPS domain
- [ ] Live website URL submitted
