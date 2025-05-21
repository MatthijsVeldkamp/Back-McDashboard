import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { addToast } from '../utils/toast';

const Dashboard = () => {
  const navigate = useNavigate();
  const [user, setUser] = useState(null);
  const [servers, setServers] = useState([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    const token = localStorage.getItem('token');
    
    if (!token) {
      navigate('/login');
      return;
    }

    const fetchUserData = async () => {
      try {
        const response = await fetch('http://127.0.0.1:8000/api/user', {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json',
          },
          credentials: 'include',
        });

        if (!response.ok) {
          throw new Error('Kon gebruikersgegevens niet ophalen');
        }

        const data = await response.json();
        setUser(data);
        
        // Fetch user's servers after getting user data
        const serversResponse = await fetch(`http://127.0.0.1:8000/api/servers/${data.id}`, {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json',
          },
          credentials: 'include',
        });

        if (serversResponse.ok) {
          const serversData = await serversResponse.json();
          setServers(serversData.servers);
        }
      } catch (error) {
        addToast(error.message);
        navigate('/login');
      } finally {
        setIsLoading(false);
      }
    };

    fetchUserData();
  }, [navigate]);

  return (
    <div>
      {/* Render your component content here */}
    </div>
  );
};

export default Dashboard; 