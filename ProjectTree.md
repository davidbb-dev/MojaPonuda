# Project Structure

```text
pva_project/
├── README.md
├── PROJECT_STRUCTURE.md
├── docker/
│   ├── .dockerignore
│   ├── .env.example
│   ├── Dockerfile
│   ├── docker-compose.yml
│   ├── entrypoint.sh
│   ├── nginx/
│   │   └── nginx.conf
│   └── php/
│       └── uploads.ini
└── src/
    ├── App/
    │   ├── Controllers/
    │   ├── Database/
    │   ├── Enums/
    │   ├── Exceptions/
    │   ├── Http/
    │   ├── Router/
    │   ├── Services/
    │   ├── Support/
    │   └── Views/
    │       ├── admin/
    │       └── partials/
    ├── config/
    │   ├── auction_config.php
    │   └── config.php
    ├── database/
    │   ├── init-db.php
    │   └── schema.sql
    └── public/
        ├── css/
        ├── icons/
        ├── img/
        ├── js/
        └── index.php
```
