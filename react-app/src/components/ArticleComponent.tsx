import {useEffect, useState} from "react";
import {Article} from "../types";

function ArticleComponent() {
    const [articles, setArticles] = useState<Article[]>([]);
    const [error, setError] = useState(null);

    useEffect(() => {
        async function fetchData() {
            try {
                const response = await fetch('/jsonapi/node/article', {
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

                const fetchedArticles = data.data.map((article: any) => ({
                    id: article.id,
                    title: article.attributes.title,
                    body: article.attributes.body.value,
                }));

                setArticles(fetchedArticles);
            } catch (error: any) {
                console.error('Error fetching data:', error);
                setError(error.message);
            }
        }

        fetchData();
    }, []);
  return (
    <div>
      <h1>Articles</h1>
        {error ? (
            <p>Error: {error}</p>
        ) : (
            <ul>
            {articles.map(article => (
                <li key={article.id}>
                <strong>Title:</strong> {article.title}<br />
                <strong>Body:</strong> {article.body}<br />
                </li>
            ))}
            </ul>
        )}
    </div>
  );
}

export default ArticleComponent;