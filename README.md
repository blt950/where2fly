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
`SENTRY_LARAVEL_DSN` - Sentry DSN URL
`DEBUGBAR_ENABLED` - Enable the debugbar


## Update
- Update the Docker container
- Remember to run `docker exec -it where2fly php artisan migrate`

## Updating Airports Database
- Download the latest Airports & Runways data from OurAirports as CSV
- Truncate the airports database
- Import the CSV into the database
- Run all the enrich commands to enrich the data of airports and other connections

## Data Sources
Airports & Runways: https://ourairports.com/

METAR: https://metar.vatsim.net/all

TAF: https://api.met.no/weatherapi/tafmetar/1.0/taf.txt?icao=ICAO

Flags: https://flagicons.lipis.dev/

## Updating Airport Database
Last update: 2024-10-19

- Temporary drop the spatial index
- Make the `coordinates` column nullable
- Truncate and then import the new CSV. Remember using the id provided in the CSV
- Run the `php artisan enrich:airports` command
- Run this SQL command to add coordinates to the airports
    ```sql
    UPDATE airports
    SET coordinates = ST_SRID(
        ST_GeomFromText(
            CONCAT('POINT(', longitude_deg, ' ', latitude_deg, ')')
        ), 4326
    );
    ```
- Re-add the spatial index
    ```sql
    ALTER TABLE `where2fly`.`airports`
    MODIFY `coordinates` POINT NOT NULL;
    ALTER TABLE `where2fly`.`airports`
    ADD SPATIAL INDEX `airports_coordinates_spatialindex` (`coordinates`);
    ```
- Update the runways as well

## API
Read more about the [API](API.md)