import React from 'react';
import {authOptions} from "@/app/api/auth/[...nextauth]/route";
import {getServerSession} from "next-auth";

export default async function DatasetsConfiguration() {
    const session = await getServerSession(authOptions);

    return (
        <div>
            {session ? <div>
                Your name is {session.user?.name}
                <h2 className="flex items-center justify-center min-h-screen">
                    Welcome to the Datasets Configuration page.
                </h2>
            </div> : <div>Access Denied</div>}
        </div>
    );
};

