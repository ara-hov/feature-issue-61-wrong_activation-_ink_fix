### Local PC environment

- ``` composer install ```
- Define a file copy the contents of ```.env.example``` and make a new file ```.env``` where change the following fields

- Open mysql client and execute ``` CREATE SCHEMA reverifi_props; ```
```
DB_CONNECTION=mysql
DB_HOST=[localhost address] 
DB_PORT=3306
DB_DATABASE=reverifi_props
DB_USERNAME=[configured username]
DB_PASSWORD=[configured password]
```

- Run Migration (we can run it with seeds)
- ``` php artisan migrate ```

- Now install Passport
- ```php artisan passport:install```



- ``` php artisan serve --host=localhost --port=8000 ```

- Enjoy Debugging !!!
