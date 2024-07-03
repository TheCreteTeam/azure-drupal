import React from 'react';
import {authOptions} from "@/app/api/auth/[...nextauth]/route";
import {getServerSession} from "next-auth";
import {redirect} from "next/navigation";

export default async function Search() {
    const session = await getServerSession(authOptions)
    if (!session) {
        redirect('/api/auth/signin?callbackUrl=/search')
    }
    return (
        <div>
            <h2 className="flex items-center justify-center min-h-screen">
                Welcome to the Search page.
            </h2>
        </div>
    );
};

