from fastapi.testclient import TestClient

from innahu_allah.web.app import app


def main() -> None:
    client = TestClient(app)
    for path in ["/", "/mushaf", "/names/1", "/admin"]:
        response = client.get(path)
        assert response.status_code == 200, f"{path} failed with {response.status_code}"
    print("Smoke test passed.")


if __name__ == "__main__":
    main()
