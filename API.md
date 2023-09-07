## How to use
- By using this free API it's required that you've a `Powered by Where2Fly` text in near proximity of the data provided to the users of your service. The text should link to `https://where2fly.today`
- The authorisation token is a bearer token, used in a header like this `Authorization: Bearer <token>`
- Remember to add `Accept: application/json` header in all of your calls to get return in json format.

### Environments
- `https://qa.where2fly.today/` for **testing data** and quality assurance. Data in this environment is often static and rarely updated, so it's easier to debug your application.
- `https://where2fly.today/` for **live** production data

### API
To get access to the API, please contact Blt950 on Discord.

## Endpoints
### GET `/api/top`
Returns the top airports as on the website.

| Param | Required | Type | Description |
| --- | --- | --- | --- |
| `continent` | No | string | Filter on continent |
| `limit` | No | integer | Limit the number of results 0-30 |

### POST `/api/top`
Returns top airports with your provided whitelist
| Param | Required | Type | Description |
| --- | --- | --- | --- |
| `whitelist` | Yes | array | Filter on selected airport ICAO codes |
| `limit` | No | integer | Limit the number of results 0-30 |

### POST `/api/search`
Returns airports matching your search query

| Param | Required | Type | Description | Default value |
| --- | --- | --- | --- | --- |
| `departure` | Yes | string | Departure airport | - |
| `continent` | Yes | string | Filter on continent | - |
| `codeletter` | Yes | string | Select aircraft type | - |
| `airtimeMin` | No | string | Minimum airtime | 0 |
| `airtimeMax` | No | string | Maximum airtime | 24 |
| `scores` | No | array* | Apply condition weather or ATC filters as described below | null |
| `metconditions` | No | string | Apply weather filters `IFR` or `VFR` | null |
| `destinationRunwayLights` | No | int* | Only show airports with runway lights | 0 |
| `destinationAirbases` | No | int* | Only show airports with airbases | 0 |
| `destinationAirportSize` | No | array | Only show airports with the selected size | airport_small, airport_medium, airport_large |
| `destinationFilter` | No | array | Filter destinations to your liking | null |
| `elevationMin` | No | string | Minimum airport elevation | 0 |
| `elevationMax` | No | string | Maximum airport elevation | 18000 |
| `rwyLengthMin` | No | string | Minimum runway length | 0 |
| `rwyLengthMax` | No | string | Maximum runway length | 16000 |
| `limit` | No | integer | Limit the number of results 0-30 | 10 |

#### Regarding array* and int*
These parameters should be supplied with an int value standalone or within an array:
- `-1` = Not allowed
- `0` = Neutral
- `1` = Allowed

Example: `scores[METAR_WINDY] = -1` to exclude all windy airports, or `destinationRunwayLights=1` to only show airports with runway lights.

#### Airport Sizes Array
The airport sizes array should contain one or more of the following values:
- `airport_small`
- `airport_medium`
- `airport_large`


## Data types

### Available scores

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

### Available codeletters
- `A` - e.g. PIPER/CESSNA
- `B` - e.g. CRJ/DHC
- `C` - e.g. A320/B737/ERJ
- `D` - e.g. A330/B767/B777
- `E` - e.g. A340/B747/B787
- `F` - e.g. A380/B748