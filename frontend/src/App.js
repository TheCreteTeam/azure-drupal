import React, { useState, useEffect } from 'react';

function App() {
  const [cars, setcars] = useState([]);
  const [error, setError] = useState(null);

  useEffect(() => {
    async function fetchData() {
      try {
        const response = await fetch('/jsonapi/node/car', {
          method: 'GET',
          headers: {
            Accept: 'application/json',
            'Content-Type': 'multipart/form-data',
          },
        });

        if (!response.ok) {
          throw new Error('Network response was not ok');
        }

        const data = await response.json();
        console.log(data);  // Log response data

        const fetchedcars = data.data.map(item => ({
          id: item.id,
          brand: item.attributes.field_brand,
          color: item.attributes.field_color,
        }));

        setcars(fetchedcars);
      } catch (error) {
        console.error('Error fetching data:', error);
        setError(error.message);
      }
    }

    fetchData();
  }, []);

  return (
      <div className="App">
        <h1>cars List</h1>
        {error ? (
            <p>Error: {error}</p>
        ) : (
            <ul>
              {cars.map(car => (
                  <li key={car.id}>
                    <strong>Brand:</strong> {car.brand}<br />
                    <strong>Color:</strong> {car.color}<br />
                  </li>
              ))}
            </ul>
        )}
      </div>
  );
}

export default App;