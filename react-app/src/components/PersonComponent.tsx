import React, {useEffect, useState} from "react";
import {Person} from "../types";


function PersonComponent() {
    const [persons, setPersons] = useState<Person[]>([]);
    const [error, setError] = useState(null);

    useEffect(() => {
        async function fetchData() {
            try {
                const response = await fetch('/jsonapi/node/person', {
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

                const fetchedPersons = data.data.map((person: any) => ({
                    id: person.id,
                    name: person.attributes.field_name,
                    age: person.attributes.field_age,
                }));

                setPersons(fetchedPersons);
            } catch (error: any) {
                console.error('Error fetching data:', error);
                setError(error.message);
            }
        }

        fetchData();
    }, []);

  return (
    <div>
        <h1>Persons List</h1>
        {error ? (
            <p>Error: {error}</p>
        ) : (
            <ul>
                {persons.map(person => (
                    <li key={person.id}>
                        <strong>Name:</strong> {person.name}<br />
                        <strong>Age:</strong> {person.age}<br />
                    </li>
                ))}
            </ul>
        )}
    </div>
  );
}

export default PersonComponent;