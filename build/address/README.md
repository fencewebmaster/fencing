# Address suggestions data (`writable/au.jsonl`)

The planner's Address fields (Download Your Project Plans, and the project plan's Edit Details) suggest
Australian streets as the customer types. Picking one fills Address, Post Code and State. The list comes
from FC's own copy of **G-NAF**, the national address file, so there is no Google key, billing or outside call.

| Piece | Where |
|---|---|
| Data file (gitignored, per site, ~70 MB) | `writable/au.jsonl` |
| Search | `AddressLookupService::search()`, served as `GET address-lookup?q=…&state=…` |
| Field behaviour | `fcAddress*` in `public/assets/js/frontend/core/functions.js` |
| Build | `build/address/build.php` (this folder) |

## Build the file

1. Download the latest **G-NAF PSV zip (GDA2020)** from
   [data.gov.au](https://data.gov.au/data/dataset/geocoded-national-address-file-g-naf) (about 1.9 GB;
   released quarterly).
2. From the project root:

       php build/address/build.php path/to/g-naf_<release>_allstates_gda2020_psv_<n>.zip

   About 80 seconds. The tables are streamed out of the zip with Info-ZIP `unzip` (Git for Windows ships
   it); pass `--unzip=path/to/unzip` if it is not on the PATH. Nothing is unpacked to disk.
3. Delete the zip.

The file has one line per street in a suburb: current streets with at least one current address, plus
confirmed streets nobody lives on yet, each with the postcode most of its addresses carry and its address
count (busier streets rank first). The first line is a meta record (release, build date, street count,
street-type abbreviations). Lines are sorted by their search key, which the lookup binary-searches, so the
file is never loaded whole and a lookup takes a few milliseconds.

## Live sites

`writable/au.jsonl` is gitignored like `products.csv`, so a deploy does not carry it: copy the file into
each site's `writable/` folder (zip it for the upload; it compresses to about 17 MB). Settings → Site Health
→ Server & App shows **Address suggestions** with the release and street count, or a warning when the file is
missing. Without it the Address fields still work; they just show no suggestions.

Re-run the build when a new release is worth having; new streets are added each quarter.

## Licence

G-NAF is open data under the Open G-NAF End User Licence Agreement (based on CC BY 4.0). The suggestion
list shows "Address data © Geoscape Australia (G-NAF)", and the meta record carries the full statement:

> Incorporates or developed using G-NAF © Geoscape Australia licensed by the Commonwealth of Australia
> under the Open Geo-coded National Address File (G-NAF) End User Licence Agreement.

The EULA forbids using G-NAF to compile addresses for sending mail without checking each against another
source. FC only uses it to help customers type their own address.
