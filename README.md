# Where2Fly
Always struggling to decide where to fly? Find some suggested destinations with fun weather and coverage!

## License

**Where2Fly** is licensed under the 
[GNU Affero General Public License, version 3](LICENSE) (**AGPLv3**).
- You are free to use, modify, and distribute this software.
- If you modify the software and make it available over a network (e.g., a web service),
  you **must** provide the complete source code to the public.
- You must keep the same license (AGPLv3) for any modifications.

For the full legal text, see the [LICENSE](LICENSE) file or visit
[https://www.gnu.org/licenses/agpl-3.0.en.html](https://www.gnu.org/licenses/agpl-3.0.en.html).

## Tech Stack
**Frontend:** Laravel Blade, React, JS and SCSS\
**Backend:** PHP/Laravel with MySQL

*You'll notice that React is only applied to the canvas of the map. The end goal is to have the whole application in React, since the current solution is sub-optimal and creates multiple page renders*

## Development Setup

### Docker
1. Setup the container by running the `docker-compose.dev.yml` from the root folder, this will bind your local folder to the container.
2. Setup the database with `docker exec -it where2fly php artisan migrate`
3. Create an application key with `docker exec -it where2fly php artisan key:generate`
4. Setup the cronjob with `* * * * * docker exec --user www-data -i where2fly php artisan schedule:run >/dev/null`
5. Import airport and runway database by following the instructions in the [airport database section](README.md#updating-airport-database)

### Environment variables

| Value                      | Description                                              | Required |
|----------------------------|----------------------------------------------------------|----------|
| `APP_URL`                  | The URL of the application                               | Yes      |
| `APP_ENV`                  | The environment of the application                       | Yes      |
| `APP_AIRLABS_KEY`          | The API key for [Airlabs](https://airlabs.co/) API       | Yes      |
| `APP_FSADDONCOMPARE_KEY`   | The API key for [FSAddonCompare](https://fsaddoncompare.com/) API | Yes      |
| `APP_DEBUG`                | Enable debug mode                                        | No       |
| `DEBUGBAR_ENABLED`         | Enable the debugbar                                      | No       |
| `SENTRY_LARAVEL_DSN`       | Sentry DSN URL                                           | No       |
| `SENTRY_TRACES_SAMPLE_RATE`| Sentry traces sample rate                                | No       |

### Caching

This application uses the OPCache to cache the compiled PHP code. Default setting is for production which means that the cache is not cleared automatically. To clear the cache, you need to restart the container if you change a file.

For development, consider turning `validate_timestamps` to `1` in the `php.ini` file to make sure that the cache is cleared automatically when a file is changed.

## Configuration

### Updating Airport Database
Last update in production: 2024-10-19

- Download the latest [Airports & Runways data from OurAirports](https://ourairports.com/data/) as CSV
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
- Make the `coordinates` column not nullable again
- Update the `runways` by truncating the data and then importing the CSV.

## Data Sources

This project uses the following data sources:

- Airports & Runways: https://ourairports.com/
- Air Traffic: https://airlabs.co/
- Flags: https://flagicons.lipis.dev/
- METAR: https://metar.vatsim.net/all
- Sceneries: https://fsaddoncompare.com/
- TAF: https://api.met.no/weatherapi/tafmetar/1.0/taf.txt?icao=ICAO

## API
Read more about the [API here](API.md).