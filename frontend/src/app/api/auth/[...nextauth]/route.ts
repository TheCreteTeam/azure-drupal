import NextAuth, {AuthOptions, TokenSet, User} from "next-auth";
import KeycloakProvider from "next-auth/providers/keycloak"
import {JWT} from "next-auth/jwt";
import {DefaultSession} from "next-auth";

export const authOptions: AuthOptions = {
    providers: [
        KeycloakProvider({
            clientId: process.env.KEYCLOAK_CLIENT_ID,
            clientSecret: process.env.KEYCLOAK_CLIENT_SECRET,
            issuer: process.env.KEYCLOAK_ISSUER
        })
    ],
    callbacks: {
        async jwt({ token, account }) {
            if (account) {
                token.idToken = account.id_token
                token.accessToken = account.access_token
                token.refreshToken = account.refresh_token
                token.expiresAt = account.expires_at
            }
            return token
        },
        async session({ session, token }) {
            session.accessToken = token.accessToken as string
            return session
        }
    }
}

declare module "next-auth" {
    interface Session {
        accessToken: string
    }
}

declare module "next-auth/jwt" {
    interface JWT {
        access_token: string
        expires_at: number
        refresh_token: string
        error?: "RefreshAccessTokenError"
    }

}

const handler = NextAuth(authOptions);
export { handler as GET, handler as POST }