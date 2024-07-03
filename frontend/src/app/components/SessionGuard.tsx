"use client";
import { signIn, useSession } from "next-auth/react";
import { ReactNode, useEffect } from "react";

export default function SessionGuard({ children }: { children: ReactNode }) {
    const { data, status } = useSession();
    useEffect(() => {
        console.log('data', data, status)
        if (!data && status !== "loading") {
            // signIn('keycloak');

        }
    }, [data]);

    return <>{children}</>;
}
