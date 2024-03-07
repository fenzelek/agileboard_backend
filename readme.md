# Agile Board

This repository contains only the backend codebase, that is the core of the system.
Please see other repositories to find all system apps (see tech stack section)

## About AgileBoard
AgileBoard is an innovative, open-source project management web application designed to streamline the complexities of <b>project and task management</b> for teams of any size. At its core, AgileBoard offers a dynamic platform for creating, organizing, and tracking tickets across various milestones or sprints, enabling teams to focus on what matters most - delivering value.

With the Agile board, teams can visualize their active sprints in a fully customizable interface, adapting the board to fit the unique workflow and processes of each project. This visual representation not only simplifies project oversight but also enhances team collaboration and productivity by providing a clear overview of task progress and dependencies.

AgileBoard goes beyond traditional project management tools by incorporating a Knowledge Base module. This feature allows teams to curate and access vital project-related articles, documents, and information, ensuring that valuable insights and resources are always within reach.

Understanding the importance of efficient resource management, AgileBoard includes a comprehensive calendar module. This tool assists in scheduling, planning, and managing team members, facilitating optimal allocation of human resources across tasks and projects.

The latest addition to AgileBoard's suite of features is the <b>time tracking</b> capability. Users can now effortlessly record their work hours manually or utilize the convenience of our computer and mobile applications for automatic time logging. This feature not only enhances project billing and accounting practices but also provides invaluable data for analyzing productivity and optimizing workflows.

AgileBoard is more than a project management tool; it's a companion in your journey towards agile excellence. By embracing AgileBoard, teams can harness the power of agility, collaboration, and information to drive project success and achieve their goals.

#### AgileBoard tech stack:
- backend is based on Laravel framwework [backend repository](https://github.com/fenzelek/agileboard_backend.git)
- frontend: AngularJS  [frontend repository](https://github.com/fenzelek/agileboard_frontend.git)
- time tracking app: Angular electron [timetracker PC repository](https://github.com/fenzelek/agileboard_timetracker_pc.git)
- time tracking mobile app : Xamarin [timetracker mobile repository](https://github.com/fenzelek/agileboard_timetracker_mobile.git)

### SPONSORS
### **[Denkkraft](https://denkkraft.eu/)**

### Security Vulnerabilities
If you discover a security vulnerability within Laravel, please send an e-mail to [opensource@denkkraft.eu](mailto:opensource@denkkraft.eu) . All security vulnerabilities will be promptly addressed.

# License
The Agile Board is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT)

# HOW TO RUN

### Prerequisites

In order to run the application you need to setup server with:

- PHP 7.4.*
- MySQL 5.7.*

### Coding standards

In order to follow code standards, you should use PSR-2 and run [PHP CS Fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer) before each commit to make sure your code is properly formatted. Base CS fixer configuration file has created as `.php_cs` to make sure every developer uses the same rules for code formatting.  Exact version that should be used is installed as dependency so you can use `./vendor/bin/php-cs-fixer --verbose fix` to run this tool   


### Installation

1. Copy `.env.example` as `.env`

2. Run `composer install`

3. In `.env` file:

    - Set `APP_KEY` to random 32 characters long string using the following command:
    
    ```
    php artisan key:generate
    ```
    
    - If you set `SQL_LOG_QUERIES` or `SQL_LOG_SLOW_QUERIES` to true (to log SQL queries), make sure you have created directory set as `SQL_LOG_DIRECTORY` in storage path and you have valid permissions to create and override files in this directory
        
    - Fill in all other data in `.env` file (database connection admin e-mail and password and so on)
            
    - If you already have existing database and you will upgrade to newer version you should fill in `.env` file `DEFAULT_COMPANY_NAME` key with company name that will be created. Otherwise for new installs, you should not fill this with any value. Provided value will be only used for single migration and should not be used in application            

4. Run    

    ```
    php artisan jwt:secret --show
    ```
   
    and put key you got into .`env` file as `JWT_SECRET` value
    
5. Run
 
    ``` 
    php artisan migrate
    ```
    
    to run migrations and seeds into database  
    
6. Depending on your system usage you might need to set **different timezone** in your `.env` file for `APP_TIMEZONE`. For example when using invoices system it's recommended to use client timezone (for example **Europe/Warsaw** instead of default UTC)   

7. You should set up cron and supervisor. Sample files are inside `deploy` directory but be aware you need to set valid paths  

### Running tests

In order to run `phpunit` tests you should prepare a few things.

1. You need to create separate database connection and set it in `.env` file in `TESTING` section
 
2. Run `php artisan migrate --database=mysql_testing` to run all migrations into this testing database (don't change `mysql_testing` when running into this command - it's not the database name!)

3. You need to have installed Linux `pdftotext` command (`apt-get install poppler-utils`) to run some tests (PDF verification). If you cannot run those tests you should set `TEST_PDF` to false in `.env` file
