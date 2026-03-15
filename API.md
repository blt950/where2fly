## How to use
- By using this free API it's required that you've a `Powered by Where2Fly` text in near proximity of the data provided to the users of your service. The text should link to `https://where2fly.today`
- The authorisation token is a bearer token, used in a header like this `Authorization: Bearer <token>`
- Remember to add `Accept: application/json` header in all of your calls to get return in json format.

### Environments
- `https://qa.where2fly.today/` for **testing data** and quality assurance. Data in this environment is often static and rarely updated, so it's easier to debug your application. Do not share data from here to your users.
- `https://where2fly.today/` for **live** production data

### Access
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
| `arrival` | Yes | string | Arrival airport | - |
| `destinations` | Yes | array | Filter continent, countries and states. Described below | - |
| `codeletter` | Yes | string | Select aircraft type | - |
| `airtimeMin` | No | string | Minimum airtime | 0 |
| `airtimeMax` | No | string | Maximum airtime | 24 |
| `scores` | No | array* | Apply condition weather or ATC filters as described below | null |
| `metconditions` | No | string | Apply weather filters `IFR` or `VFR` | null |
| `destinationRunwayLights` | No | int* | Only show airports with runway lights | 0 |
| `destinationAirbases` | No | int* | Only show airports with airbases | -1 |
| `destinationAirportSize` | No | array | Only show airports with the selected size | airport_small, airport_medium, airport_large |
| `destinationFilter` | No | array | Filter destinations to your liking | null |
| `temperaturenMin` | No | string | Minimum temperature | -60 |
| `temperaturenMax` | No | string | Maximum temperature | 60 |
| `elevationMin` | No | string | Minimum airport elevation | 0 |
| `elevationMax` | No | string | Maximum airport elevation | 18000 |
| `rwyLengthMin` | No | string | Minimum runway length | 0 |
| `rwyLengthMax` | No | string | Maximum runway length | 16000 |
| `limit` | No | integer | Limit the number of results 0-30 | 10 |

#### Regarding array* and int*
These parameters should be supplied with an int value standalone or within an array:
- `-1` = Exclude
- `0` = Neutral
- `1` = Must be present

Example: `scores[METAR_WINDY] = -1` to exclude all windy airports, or `destinationRunwayLights=1` to only show airports with runway lights.

#### Airport Sizes Array
The airport sizes array should contain one or more of the following values:
- `airport_small`
- `airport_medium`
- `airport_large`

#### What about airlines and routes?
Due to the terms of service of the data provider, this won't be available for further distribution through the Where2Fly API.

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

### Available destinations

You may filter on continents, countries and/or US states. If you send no values, all destinations will be included. The input array should be formated like this and all entries as array of strings:

```
"destinations": {
    "continents": null,
    "countries": ["NL", "DE"],
    "states": null
}
```

#### Continents
- `AF` - Africa
- `AS` - Asia
- `EU` - Europe
- `NA` - North America
- `OC` - Oceania
- `SA` - South America

#### Countries
Use two letter ISO 3166-1 alpha-2 country codes. E.g. `NL` for the Netherlands, `US` for the United States, etc.

To  only search for domestic flights write `Domestic` **as string** in this field.

#### US States
Use two letter US state codes with a `US-` prefix. E.g. `US-CA` for California, `US-NY` for New York, etc.

### Available codeletters
This is used to calculate airtime and find compatible airports. Select aircraft closes to what user want to fly.
- `A` - PIPER/CESSNA
- `B` - CRJ/DHC
- `C` - A320/B737/ERJ
- `D` - B767/A310
- `E` - B777/B787/A330
- `F` - 747-8/A380