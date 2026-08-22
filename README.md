# Where2Fly
Always struggling to decide where to fly? Find some suggested destinations with fun weather and coverage!

![w2f](https://github.com/user-attachments/assets/80281d26-c74c-4712-be12-f394ff303f15)

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

### MySQL Client
The container image uses the Oracle MySQL Client (8.4.x) installed from Oracle's official Debian packages instead of Debian's `default-mysql-client` (MariaDB). This ensures full compatibility with MySQL 8.4 features and protocol behavior. If you need to update the version, change the `MYSQL_CLIENT_VERSION` build argument in the `Dockerfile`.

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
| `APP_GITHUB_KEY`           | Key to fetch feedback page                               | No       |
| `DEBUGBAR_ENABLED`         | Enable the debugbar                                      | No       |
| `SENTRY_LARAVEL_DSN`       | Sentry DSN URL                                           | No       |
| `SENTRY_TRACES_SAMPLE_RATE`| Sentry traces sample rate                                | No       |

### Fonts
All required text fonts are included in this project. Icons are provided by Font Awesome Pro and need to be manually added by you to the project due to licensing restrictions.

Add the following fonts (v7.0.0 or later) to your local project `resources/fonts` folder:
- fa-brands-400.woff2
- fa-sharp-regular-400.woff2
- fa-sharp-solid-900.woff2

### Caching

This application uses the OPCache to cache the compiled PHP code. Default setting is for production which means that the cache is not cleared automatically. To clear the cache, you need to restart the container if you change a file.

For development, consider turning `validate_timestamps` to `1` in the `php.ini` file to make sure that the cache is cleared automatically when a file is changed.

## Configuration

### Updating Airport Database
Last update in production: 2026-07-29

- Download the latest [Airports & Runways data from OurAirports](https://ourairports.com/data/) as CSV
- Temporarily drop the spatial index and make the `coordinates` column nullable
    ```sql
    ALTER TABLE `where2fly`.`airports`
    DROP INDEX `airports_coordinates_spatialindex`;
    ALTER TABLE `where2fly`.`airports`
    MODIFY `coordinates` POINT SRID 4326 NULL;
    ```
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
- Make the `coordinates` column not nullable again and re-add the spatial index
    ```sql
    ALTER TABLE `where2fly`.`airports`
    MODIFY `coordinates` POINT NOT NULL SRID 4326;
    ALTER TABLE `where2fly`.`airports`
    ADD SPATIAL INDEX `airports_coordinates_spatialindex` (`coordinates`);
    ```
- Update the `runways` by truncating the data and then importing the CSV.

## Debugging the map

Airport dots and ICAO labels are drawn by the GPU inside a single `<canvas>`, so there are no
DOM nodes to inspect — devtools' element picker will only ever find the canvas, and there is
deliberately no global handle on the map.

To experiment with label styling, add a temporary `window.__map = instance` in
`MapProvider.jsx` and use `__map.setPaintProperty('airports-label-large', 'text-halo-width', 3)`
and friends from the console. Do not call it `window.map` — the `<aside id="map">` element
already claims that name through the browser's implicit named-access global.

The label layers are `airports-label-{large,medium,small,pinned}` plus `user-list-label-*`;
their styling lives in `resources/js/components/utils/airportLayerSpec.js`.

Layer preferences live in `localStorage.mapPreferences` — clear it to get the defaults back.

## Data Sources

This project uses the following data sources:

- Airports & Runways: https://ourairports.com/
- Air Traffic: https://airlabs.co/
- Flags: https://flagicons.lipis.dev/
- METAR/TAF: https://aviationweather.gov/data/api/
- Sceneries: https://fsaddoncompare.com/

## API
Read more about the [API here](API.md).
