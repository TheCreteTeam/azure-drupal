// "use client";
// import { signIn, useSession } from "next-auth/react";
// import { ReactNode, useEffect } from "react";
//
// export default function SessionGuard({ children }: { children: ReactNode }) {
//     const { data: session } = useSession();
//
//     useEffect(() => {
//         if (session?.error === "RefreshAccessTokenError") {
//             signIn(); // Force sign in to hopefully resolve error
//         }
//     }, [session]);
//
//     return <>{children}</>;
// }
