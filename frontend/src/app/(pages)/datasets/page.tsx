"use client";
import React, {useEffect, useState} from 'react';
import {getSession, signIn, useSession} from "next-auth/react";

export default function Datasets() {
    const [data1, setData1] = useState<string | null>(null);
    const [data2, setData2] = useState<string | null>(null);

    const[loading, setLoading] = useState<boolean>(true);

    useEffect(() => {
        const securePage = async () => {
            const session = await getSession();
            if (!session) {
                // signIn('keycloak');
            } else {
                console.log('session', session);
                setLoading(false);
            }
        }
        securePage();
    }, []);

    if (loading) {
        return <h1>Loading...</h1>
    }

    // const { data: session } = useSession();
    // const accessToken = session?.accessToken;
    // useEffect(() => {
    //     const fetchData = async () => {
    //         const response = await fetch('http://localhost:8085/hello3');
    //         const data = await response.text()
    //         console.log(data);
    //         setData1(data);
    //     }
    //
    //     const fetchData2 = async () => {
    //         if (!accessToken) {
    //             return;
    //         }
    //         const response = await fetch('http://localhost:8085/hello2', {
    //             headers: {
    //                 Authorization: `Bearer ${accessToken}`
    //             }
    //         });
    //         const data2 = await response.text()
    //         console.log(data2);
    //         setData2(data2);
    //     }
    //
    //     fetchData();
    //     fetchData2();
    // });

    return (
        <div>
            <h2 className="flex items-center justify-center min-h-screen">
                Welcome to the Datasets page.
            </h2>
            <div>
                {/*<h1>{data1 || 'Loading...'}</h1>*/}
            </div>
        </div>
    );
};

