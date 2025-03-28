import { ApolloClient, InMemoryCache, createHttpLink } from '@apollo/client';
import { setContext } from '@apollo/client/link/context';
import { API_ENDPOINTS } from '../utils/config';

// Create an HTTP link to the Drupal GraphQL endpoint
const httpLink = createHttpLink({
  uri: API_ENDPOINTS.GRAPHQL,
  credentials: 'include', // Include cookies for session authentication
});

// Add authentication headers
const authLink = setContext((_, { headers }) => {
  // Get the authentication token from local storage if it exists
  const token = localStorage.getItem('authToken');
  
  // Return the headers to the context so httpLink can read them
  return {
    headers: {
      ...headers,
      authorization: token ? `Bearer ${token}` : '',
    }
  };
});

// Create the Apollo Client
export const client = new ApolloClient({
  link: authLink.concat(httpLink),
  cache: new InMemoryCache({
    typePolicies: {
      Query: {
        fields: {
          products: {
            // Merge function for paginated results
            keyArgs: ['filter'],
            merge(existing = { edges: [] }, incoming) {
              return {
                ...incoming,
                edges: [...(existing.edges || []), ...(incoming.edges || [])],
              };
            },
          },
        },
      },
    },
  }),
  defaultOptions: {
    watchQuery: {
      fetchPolicy: 'cache-and-network',
    },
  },
}); 