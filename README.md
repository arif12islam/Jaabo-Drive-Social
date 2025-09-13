# Jaabo - Drive Social

Jaabo is a ride-sharing web service exclusively for university students. Our mission is to transform the daily commute into a social, safe, and cost-effective experience. With the slogan **"Drive Social,"** Jaabo connects students with their peers, making every trip an opportunity to build community.

---

### **Key Features**

* **Post a Ride**: Drivers can easily post their trip details, including the route, schedule, and number of available seats.
* **Book a Ride**: Rirders can browse available rides from fellow students and book a seat with a few simple clicks.
* **Manage Trips**: Users have full control over their plans:
    * Drivers can **delete** a posted ride if their plans change.
    * Riders can **cancel** a booked ride, freeing up the seat for another student.

---

### **Getting Started**

#### Prerequisites

To run Jaabo locally, you'll need the following installed:

* A web server (e.g., Apache, Nginx)
* PHP (version 7.4 or higher is recommended)
* Composer
* A database (e.g., MySQL, PostgreSQL)

#### Installation

1.  Clone the repository:
    ```sh
    git clone [https://github.com/your-username/jaabo.git]https://github.com/arif12islam/Jaabo-Drive-Social
    cd jaabo
    ```
2.  Install dependencies using Composer:
    ```sh
    composer install
    ```
3.  Set up your environment. Copy the example environment file:
    ```sh
    cp .env.example .env
    ```
4.  Configure your database connection in the `.env` file and run migrations:
    ```
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=your_db_name
    DB_USERNAME=your_db_user
    DB_PASSWORD=your_db_password
    ```
5.  Start your local web server.

---

### **Technology Stack**

* **Backend**: PHP (using a framework like Laravel or Symfony if applicable)
* **Frontend**: HTML, CSS, JavaScript
* **Database**: MySQL
* **Dependencies**: Composer