# MojaPonuda

MojaPonuda is a PHP MVC marketplace for buying and selling items. It supports both regular fixed-price listings and auctions with bidding.

Users can register, create and manage listings, buy items, place bids, send messages, leave reviews, and keep track of their purchases and sales. The application also has a separate admin section.

## Features

* User registration and login
* Fixed-price listings
* Auctions and bidding
* Buy Now
* Listing creation and management
* Multiple images per listing
* Favorites
* User messaging
* Notifications
* Reviews and ratings
* Purchase and sales history
* Admin section

## Tech stack

* PHP 8.3
* MySQL 8.0
* PDO
* JavaScript
* HTML / CSS
* Nginx
* Docker
* Docker Compose

## Running the project

The project runs locally using Docker.

First, create the `.env` file:

```bash
cd docker
cp .env.example .env
```

Set the database and admin credentials in `.env`, for example:

```env
DB_USER=your_db_user
DB_PASS=your_db_password
MYSQL_ROOT_PASSWORD=your_mysql_root_password

ADMIN_USERNAME=admin
ADMIN_PASSWORD=your_admin_password
ADMIN_EMAIL=admin@mojaponuda.local
```

Do not commit `.env` to the repository.

Build and start the containers:

```bash
docker compose up --build
```

The application will be available at:

http://localhost:8000

The database is created and initialized when the containers are started.

To stop the containers:

```bash
docker compose down
```

If you want to remove the database volume and start with an empty database:

```bash
docker compose down -v
```
