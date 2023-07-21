# Where2Fly
A web service suggesting where to fly next.

## Configuration
- Setup the database `docker exec -it where2fly php artisan migrate`
- Import the data source Airports & Runways into the SQL directly
- Remember to setup a cronjob
    ```
    * * * * * docker exec --user www-data -i where2fly php artisan schedule:run >/dev/null
    ```

### Available environment variables

#### Required
`APP_URL=http://localhost` - The URL of the application
`APP_ENV` - The environment of the application

#### Optional
`APP_DEBUG` - Enable debug mode
`DEBUGBAR_ENABLED` - Enable the debugbar


## Update
- Update the Docker container
- Remember to run `docker exec -it where2fly php artisan migrate`

## Data Sources
Airports & Runways: https://ourairports.com/

METAR: https://metar.vatsim.net/all

TAF: https://api.met.no/weatherapi/tafmetar/1.0/taf.txt?icao=ICAO

Flags: https://flagicons.lipis.dev/