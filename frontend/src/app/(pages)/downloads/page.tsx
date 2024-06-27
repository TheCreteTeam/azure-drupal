import React from 'react';


const Downloads = () => {
    return (
        <div className="flex flex-col items-center justify-center min-h-screen">
            <h2>
                If the team Gives drupal to the Crete team, then please click the button below.
            </h2>
            <a href="/Letter_of_resignation.docx" download={"Letter_of_resignation.docx"} color="blue" className="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                <span>Click me</span>
            </a>
        </div>
    );
};

export default Downloads;