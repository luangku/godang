import { lazy, Suspense, useEffect } from 'react';
import {
    BrowserRouter,
    Navigate,
    Route,
    Routes,
    useLocation,
} from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { useAuth } from './stores/auth';
import Shell from './components/Shell';

const Login = lazy(() => import('./pages/Login'));
const ResetPassword = lazy(() => import('./pages/ResetPassword'));
const Dashboard = lazy(() => import('./pages/Dashboard'));
const Posts = lazy(() => import('./pages/Posts'));
const PostForm = lazy(() => import('./pages/PostForm'));
const PostView = lazy(() => import('./pages/PostView'));
const Trash = lazy(() => import('./pages/Trash'));
const Comments = lazy(() => import('./pages/Comments'));
const Categories = lazy(() => import('./pages/Categories'));
const Tags = lazy(() => import('./pages/Tags'));
const Media = lazy(() => import('./pages/Media'));
const MediaTrash = lazy(() => import('./pages/MediaTrash'));
const Users = lazy(() => import('./pages/Users'));
const Settings = lazy(() => import('./pages/Settings'));

const queryClient = new QueryClient({
    defaultOptions: {
        queries: { retry: 1, refetchOnWindowFocus: false },
    },
});

function FullScreenLoader() {
    return (
        <div className="min-h-screen flex items-center justify-center bg-(--color-bg)">
            <div className="w-8 h-8 border-2 border-(--color-border) border-t-(--color-accent) rounded-full animate-spin" />
        </div>
    );
}

function AuthGate({ children }) {
    const { status, fetchMe } = useAuth();
    const location = useLocation();

    useEffect(() => {
        fetchMe();
    }, []);

    if (status === 'loading') return <FullScreenLoader />;

    if (status === 'guest') {
        return <Navigate to="/admin/login" replace state={{ from: location.pathname }} />;
    }

    return children;
}

function LoginRoute() {
    const { status } = useAuth();
    const location = useLocation();

    useEffect(() => {
        useAuth.getState().fetchMe();
    }, []);

    if (status === 'loading') return <FullScreenLoader />;

    if (status === 'admin') {
        const from = location.state?.from && location.state.from !== '/admin/login'
            ? location.state.from
            : '/admin';
        return <Navigate to={from} replace />;
    }

    return <Login />;
}

export default function App() {
    return (
        <QueryClientProvider client={queryClient}>
            <BrowserRouter>
                <Suspense fallback={<FullScreenLoader />}>
                    <Routes>
                        <Route path="/admin/login" element={<LoginRoute />} />
                        <Route path="/admin/reset-password" element={<ResetPassword />} />
                        <Route
                            path="/admin"
                            element={
                                <AuthGate>
                                    <Shell />
                                </AuthGate>
                            }
                        >
                            <Route index element={<Dashboard />} />
                            <Route path="posts" element={<Posts />} />
                            <Route path="posts/trash" element={<Trash />} />
                            <Route path="posts/create" element={<PostForm />} />
                            <Route path="posts/:id/edit" element={<PostForm />} />
                            <Route path="posts/:id/view" element={<PostView />} />
                            <Route path="comments" element={<Comments />} />
                            <Route path="categories" element={<Categories />} />
                            <Route path="tags" element={<Tags />} />
                            <Route path="media" element={<Media />} />
                            <Route path="media/trash" element={<MediaTrash />} />
                            <Route path="users" element={<Users />} />
                            <Route path="settings" element={<Settings />} />
                        </Route>
                        <Route path="*" element={<Navigate to="/admin" replace />} />
                    </Routes>
                </Suspense>
            </BrowserRouter>
        </QueryClientProvider>
    );
}
