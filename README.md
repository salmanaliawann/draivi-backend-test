## Draivi Backend Test

### Project Overview

This project consists of two parts:

#### Part 1: Data Fetching and Storing
- A PHP script automatically downloads the Alko daily price list, parses the data, and stores it in a MySQL database. 
- The script also fetches the daily EUR to GBP exchange rate from the Currencylayer API, converts the prices to GBP, and updates the database. 
- The script is designed to be re-runnable, updating existing entries without creating duplicates.

#### Part 2: Front-end Interaction
- A simple front-end interface was built using PHP, JavaScript, and AJAX.
- It includes two buttons: 
  - **List**: Displays the products from the database in a table.
  - **Empty**: Clears the table.
- Each product row has buttons for modifying the order amount:
  - **Add**: Increases the order amount.
  - **Clear**: Resets the order amount to 0.
- Changes are updated in the database and reflected on the page without needing a reload.
