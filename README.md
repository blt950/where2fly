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

## API

- By using this free API it's required that you've a `Powered by Where2Fly` text in near proximity of the data provided to the users of your service. The text should link to `https://where2fly.today`
- The authorisation token is a bearer token, used in a header like this `Authorization: Bearer <token>`
- Remember to add `Accept: application/json` header in all of your calls to get return in json format.

### Endpoints
#### GET `/api/top`
Returns the top airports as on the website.

| Param | Required | Type | Description |
| --- | --- | --- | --- |
| `continent` | No | string | Filter on continent |

#### POST `/api/top`
Returns top airports with your provided whitelist
| Param | Required | Type | Description |
| --- | --- | --- | --- |
| `whitelist` | Yes | array | Filter on selected airport ICAO codes |

#### POST `/api/search`
Returns airports matching your search query

| Param | Required | Type | Description | Default value |
| --- | --- | --- | --- | --- |
| `departure` | Yes | string | Departure airport | - |
| `codeletter` | Yes | string | Select aircraft type | - |
| `rwyLengthMin` | No | string | Minimum runway length | 0 |
| `rwyLengthMax` | No | string | Maximum runway length | 16000 |
| `airtimeMin` | No | string | Minimum airtime | 0 |
| `airtimeMax` | No | string | Maximum airtime | 24 |
| `elevationMin` | No | string | Minimum airport elevation | 0 |
| `elevationMax` | No | string | Maximum airport elevation | 18000 |
| `scores` | No | array | Apply weather or ATC filters | null |
| `metconditions` | No | string | Apply weather filters `IFR` or `VFR` | null |
| `arrivalWhitelist` | No | array | Only show whitelisted arrival airports | null |

### Data types

#### Available scores
- `METAR_WINDY`
- `METAR_GUSTS`
- `METAR_CROSSWIND`
- `METAR_SIGHT`
- `METAR_RVR`
- `METAR_CEILING`
- `METAR_FOGGY`
- `METAR_HEAVY_RAIN`
- `METAR_HEAVY_SNOW`
- `METAR_THUNDERSTORM`
- `VATSIM_ATC`
- `VATSIM_EVENT`
- `VATSIM_POPULAR`

#### Available codeletters
- `A` - e.g. PIPER/CESSNA
- `B` - e.g. CRJ/DHC
- `C` - e.g. A320/B737/ERJ
- `D` - e.g. A330/B767/B777
- `E` - e.g. A340/B747/B787
- `F` - e.g. A380/B748