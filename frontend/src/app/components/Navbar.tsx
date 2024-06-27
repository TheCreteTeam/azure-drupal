import React from 'react';
import Link from 'next/link';

const Navbar = () => {
    return (
        <header>
            <div className="flex justify-center p-4">
                <Link href="/">
                    <button className="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mr-5">
                        Home
                    </button>
                </Link>
                <Link href="/datasets">
                    <button className="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mr-5">
                        Datasets
                    </button>
                </Link>
                <Link href="/datasets-configuration">
                    <button className="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mr-5">
                        Datasets Configuration
                    </button>
                </Link>
                <Link href="/search">
                    <button className="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mr-5">
                        Search
                    </button>
                </Link>
                <Link href="/downloads">
                    <button className="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Downloads
                    </button>
                </Link>
            </div>
        </header>
    );
};

export default Navbar;