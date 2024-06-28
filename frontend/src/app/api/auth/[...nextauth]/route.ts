import NextAuth, {AuthOptions, TokenSet, User} from "next-auth";
import KeycloakProvider from "next-auth/providers/keycloak"
import {JWT} from "next-auth/jwt";

export const authOptions: AuthOptions = {
    providers: [
        KeycloakProvider({
            clientId: process.env.KEYCLOAK_CLIENT_ID,
            clientSecret: process.env.KEYCLOAK_CLIENT_SECRET,
            issuer: process.env.KEYCLOAK_ISSUER
        })
    ]
    // session: {
    //     maxAge: 60 * 30
    // },
    // callbacks: {
    //     async session({ session, token }) {
    //         if (token.user) {
    //             session.user = token.user as User
    //         }
    //         return session
    //     },
    //     async jwt({token, user, account, profile}) {
    //         if (account) {
    //             const userProfile: User = {
    //                 id: token.sub!,
    //                 name: profile?.name,
    //                 email: profile?.email,
    //                 image: token?.picture,
    //             }
    //
    //             return {
    //                 access_token: account.access_token,
    //                 expires_at: account.expires_at,
    //                 refresh_token: account.refresh_token,
    //                 user: userProfile,
    //             }
    //         }
    //         // we take a buffer of one minute(60 * 1000 ms)
    //         if (typeof token.expiresAt === 'number' && Date.now() < (token.expiresAt! * 1000 - 60 * 1000)) {
    //             return token
    //         } else {
    //             try {
    //                 console.log("Refreshing access token")
    //                 const response = await fetch(`${process.env.KEYCLOAK_ISSUER}/protocol/openid-connect/token`, {
    //                     headers: { "Content-Type": "application/x-www-form-urlencoded" },
    //                     body: new URLSearchParams({
    //                         grant_type: "refresh_token",
    //                         refresh_token: token.refreshToken! as string,
    //                         client_id: process.env.KEYCLOAK_CLIENT_ID,
    //                         client_secret: process.env.KEYCLOAK_CLIENT_SECRET
    //                     }),
    //                     method: "POST"
    //                 });
    //                 console.log("response", response)
    //
    //                 const tokens: TokenSet = await response.json()
    //
    //                 if (!response.ok) throw tokens
    //
    //                 return {
    //                     ...token, // Keep the previous token properties
    //                     idToken: tokens.id_token,
    //                     accessToken: tokens.access_token,
    //                     expiresAt: Math.floor(Date.now() / 1000 + (tokens.expires_in as number)),
    //                     refreshToken: tokens.refresh_token ?? token.refreshToken,
    //                 };
    //             } catch (error) {
    //                 console.error("Error refreshing access token", error)
    //                 return {...token, error: "RefreshAccessTokenError"}
    //             }
    //         }
    //     }
    // }
}

// declare module "next-auth" {
//     interface Session {
//         error?: "RefreshAccessTokenError"
//     }
// }
//
// declare module "next-auth/jwt" {
//     interface JWT {
//         access_token: string
//         expires_at: number
//         refresh_token: string
//         error?: "RefreshAccessTokenError"
//     }
// }

const handler = NextAuth(authOptions);
export { handler as GET, handler as POST }