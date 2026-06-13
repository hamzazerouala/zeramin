import { Routes, Route } from 'react-router-dom';
import Layout from '@/components/Layout';
import ProtectedRoute from '@/components/ProtectedRoute';
import Home from '@/pages/Home';
import ProductDetail from '@/pages/ProductDetail';
import Shop from '@/pages/Shop';
import CartPage from '@/pages/Cart';
import Checkout from '@/pages/Checkout';
import Login from '@/pages/Login';
import Register from '@/pages/Register';
import Orders from '@/pages/account/Orders';
import OrderDetail from '@/pages/account/OrderDetail';
import Wishlist from '@/pages/account/Wishlist';
import Profile from '@/pages/account/Profile';
import SellerDashboard from '@/pages/seller/Dashboard';
import SellerProducts from '@/pages/seller/Products';
import ImportProduct from '@/pages/seller/ImportProduct';
import SellerOrders from '@/pages/seller/Orders';
import SellerSettings from '@/pages/seller/Settings';
import Admin from '@/pages/admin/Admin';
import NotFound from '@/pages/NotFound';

export default function App() {
    return (
        <Routes>
            <Route element={<Layout />}>
                <Route path="/" element={<Home />} />
                <Route path="/products/:slug" element={<ProductDetail />} />
                <Route path="/shops/:slug" element={<Shop />} />
                <Route path="/cart" element={<CartPage />} />
                <Route path="/checkout" element={<Checkout />} />
                <Route path="/login" element={<Login />} />
                <Route path="/register" element={<Register />} />

                <Route element={<ProtectedRoute />}>
                    <Route path="/account/orders" element={<Orders />} />
                    <Route path="/account/orders/:id" element={<OrderDetail />} />
                    <Route path="/account/wishlist" element={<Wishlist />} />
                    <Route path="/account/profile" element={<Profile />} />
                </Route>

                <Route element={<ProtectedRoute requireSeller />}>
                    <Route path="/seller" element={<SellerDashboard />} />
                    <Route path="/seller/products" element={<SellerProducts />} />
                    <Route path="/seller/products/import" element={<ImportProduct />} />
                    <Route path="/seller/orders" element={<SellerOrders />} />
                    <Route path="/seller/settings" element={<SellerSettings />} />
                </Route>

                <Route element={<ProtectedRoute requireAdmin />}>
                    <Route path="/admin" element={<Admin />} />
                </Route>

                <Route path="*" element={<NotFound />} />
            </Route>
        </Routes>
    );
}
